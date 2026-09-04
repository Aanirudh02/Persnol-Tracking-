<?php

namespace App\Services\Maps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsmMapProvider implements MapProviderInterface
{
    public function searchPlaces(string $query): array
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? '');
        if (strlen($query) < 3) {
            return [];
        }

        $cacheKey = 'geo:search:v4:'.md5(mb_strtolower($query));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $merged = array_merge(
            $this->searchPhoton($query),
            $this->searchNominatim($query),
            $this->localLandmarks($query)
        );

        $results = $this->rankResults($query, $merged);

        if ($results === []) {
            foreach ($this->queryVariants($query) as $variant) {
                $results = $this->rankResults($variant, array_merge(
                    $this->searchPhoton($variant),
                    $this->searchNominatim($variant),
                    $this->localLandmarks($variant)
                ));
                if ($results !== []) {
                    break;
                }
            }
        }

        if ($results !== []) {
            Cache::put($cacheKey, $results, now()->addDay());
        }

        return $results;
    }

    public function routeDistanceKm(array $waypoints): ?float
    {
        $points = array_values(array_filter($waypoints, fn ($w) => isset($w['lat'], $w['lng'])));
        if (count($points) < 2) {
            return null;
        }

        $coords = collect($points)
            ->map(fn ($w) => round((float) $w['lng'], 5).','.round((float) $w['lat'], 5))
            ->implode(';');

        $cacheKey = 'geo:route:v3:'.md5($coords);

        $cached = Cache::get($cacheKey);
        if (is_numeric($cached)) {
            return (float) $cached;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'LifeTracker/1.0 (personal scooter tracker)',
            ])
                ->timeout(12)
                ->get("https://router.project-osrm.org/route/v1/driving/{$coords}", [
                    'overview' => 'false',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $meters = data_get($response->json(), 'routes.0.distance');
            if ($meters === null) {
                return null;
            }

            $km = round(((float) $meters) / 1000, 2);
            Cache::put($cacheKey, $km, now()->addDays(7));

            return $km;
        } catch (\Throwable $e) {
            Log::warning('OSRM route failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Curated Coimbatore landmarks when OSM returns the wrong same-name place.
     *
     * @return array<int, array{label: string, lat: float, lng: float}>
     */
    protected function localLandmarks(string $query): array
    {
        $q = mb_strtolower($query);
        $hits = [];

        $isGoldwins = str_contains($q, 'goldwin')
            || (str_contains($q, 'chinni') && (str_contains($q, '641062') || str_contains($q, 'aerodrome') || str_contains($q, 'civil')));
        if ($isGoldwins) {
            $hits[] = [
                'label' => '112 Goldwins, Civil Aerodrome Post, Chinniyampalayam, Coimbatore, Tamil Nadu 641062',
                'lat' => 11.03085,
                'lng' => 77.04295,
            ];
        }

        $isTexPark = (str_contains($q, 'tex park') || str_contains($q, 'texpark') || (str_contains($q, '414') && str_contains($q, 'nehru')))
            && ! str_contains($q, 'urolog');
        $isNehruWest = str_contains($q, 'nehru nagar west')
            || (str_contains($q, 'nehru') && str_contains($q, '641014'));
        if ($isTexPark || $isNehruWest) {
            $hits[] = [
                'label' => '414-A Tex Park Road, Nehru Nagar West, Coimbatore, Tamil Nadu 641014',
                'lat' => 11.01855,
                'lng' => 77.02685,
            ];
        }

        if (str_contains($q, 'imik') || (str_contains($q, 'nehru nagar') && str_contains($q, 'technolog'))) {
            $hits[] = [
                'label' => 'IMIK Technologies, Nehru Nagar, Coimbatore, Tamil Nadu',
                'lat' => 11.01855,
                'lng' => 77.02685,
            ];
        }

        return $hits;
    }

    /**
     * @param  array<int, array{label: string, lat: float, lng: float}>  $rows
     * @return array<int, array{label: string, lat: float, lng: float}>
     */
    protected function rankResults(string $query, array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $tokens = $this->importantTokens($query);
        $postcodes = $this->extractPostcodes($query);
        $biasLat = (float) config('services.maps.bias_lat', 11.0168);
        $biasLng = (float) config('services.maps.bias_lng', 76.9558);
        $q = mb_strtolower($query);

        return collect($rows)
            ->filter(fn ($row) => ! empty($row['label']) && ! empty($row['lat']) && ! empty($row['lng']))
            ->unique(fn ($row) => round($row['lat'], 4).','.round($row['lng'], 4))
            ->map(function ($row) use ($tokens, $postcodes, $biasLat, $biasLng, $q) {
                $label = mb_strtolower($row['label']);
                $tokenHits = 0;
                foreach ($tokens as $token) {
                    if (str_contains($label, $token)) {
                        $tokenHits++;
                    }
                }
                $postcodeHits = 0;
                foreach ($postcodes as $code) {
                    if (str_contains($label, $code)) {
                        $postcodeHits += 2;
                    }
                }
                $distanceKm = $this->haversineKm($biasLat, $biasLng, (float) $row['lat'], (float) $row['lng']);
                $score = ($tokenHits * 35) + ($postcodeHits * 80) - min($distanceKm, 200);

                if (str_contains($q, 'tex park') || str_contains($q, 'texpark')) {
                    if (str_contains($label, 'tex park') || str_contains($label, 'texpark')) {
                        $score += 120;
                    }
                    if (str_contains($label, 'urolog') || str_contains($label, 'hospital') || str_contains($label, 'clinic')) {
                        $score -= 200;
                    }
                }
                if (str_contains($q, 'goldwin') && str_contains($label, 'goldwin')) {
                    $score += 100;
                }
                if (str_contains($q, '641062') && str_contains($label, '641062')) {
                    $score += 100;
                }
                if (str_contains($q, '641014') && str_contains($label, '641014')) {
                    $score += 100;
                }
                if (str_contains($q, 'chinni') && str_contains($label, 'pollachi') && ! str_contains($label, 'coimbatore')) {
                    $score -= 150;
                }

                $row['_score'] = $score;

                return $row;
            })
            ->sortByDesc('_score')
            ->map(fn ($row) => [
                'label' => $row['label'],
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lng'],
            ])
            ->values()
            ->take(8)
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function extractPostcodes(string $query): array
    {
        preg_match_all('/\b(\d{6})\b/', $query, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @return array<int, string>
     */
    protected function importantTokens(string $query): array
    {
        $stop = ['post', 'the', 'and', 'near', 'road', 'street', 'india', 'tamil', 'nadu', 'west', 'east'];
        $parts = preg_split('/[\s,]+/', mb_strtolower($query)) ?: [];

        return collect($parts)
            ->map(fn ($p) => trim($p))
            ->filter(fn ($p) => strlen($p) >= 4 && ! in_array($p, $stop, true) && ! ctype_digit($p))
            ->unique()
            ->values()
            ->all();
    }

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    protected function searchPhoton(string $query): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'LifeTracker/1.0 (personal scooter tracker)',
                'Accept' => 'application/json',
            ])
                ->timeout(10)
                ->get('https://photon.komoot.io/api/', [
                    'q' => $query,
                    'limit' => 8,
                    'lang' => 'en',
                    'lat' => (float) config('services.maps.bias_lat', 11.0168),
                    'lon' => (float) config('services.maps.bias_lng', 76.9558),
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('features') ?? [])
                ->map(function ($feature) {
                    $props = $feature['properties'] ?? [];
                    $coords = $feature['geometry']['coordinates'] ?? null;
                    if (! is_array($coords) || count($coords) < 2) {
                        return null;
                    }

                    $parts = array_filter([
                        $props['name'] ?? null,
                        $props['street'] ?? null,
                        $props['housenumber'] ?? null,
                        $props['district'] ?? null,
                        $props['city'] ?? ($props['town'] ?? ($props['village'] ?? null)),
                        $props['county'] ?? null,
                        $props['state'] ?? null,
                        $props['postcode'] ?? null,
                        $props['country'] ?? null,
                    ]);

                    $label = implode(', ', array_unique(array_map('strval', $parts)));
                    if ($label === '') {
                        return null;
                    }

                    return [
                        'label' => $label,
                        'lat' => (float) $coords[1],
                        'lng' => (float) $coords[0],
                    ];
                })
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Photon search failed: '.$e->getMessage());

            return [];
        }
    }

    protected function searchNominatim(string $query): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'LifeTracker/1.0 (personal scooter tracker; contact: local)',
                'Accept' => 'application/json',
            ])
                ->timeout(10)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 8,
                    'countrycodes' => 'in',
                    'viewbox' => '76.70,11.20,77.20,10.80',
                    'bounded' => 0,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json() ?? [])
                ->map(fn ($row) => [
                    'label' => $row['display_name'] ?? '',
                    'lat' => (float) ($row['lat'] ?? 0),
                    'lng' => (float) ($row['lon'] ?? 0),
                ])
                ->filter(fn ($row) => $row['label'] !== '' && $row['lat'] && $row['lng'])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Nominatim search failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    protected function queryVariants(string $query): array
    {
        $variants = [];
        $parts = array_values(array_filter(array_map('trim', preg_split('/,/', $query) ?: [])));

        if (count($parts) >= 2) {
            $variants[] = implode(', ', array_slice($parts, -3));
            $variants[] = implode(', ', array_slice($parts, -2));
            $variants[] = implode(' ', array_slice($parts, 0, 3));
        }

        $variants[] = preg_replace('/^\d+[,\s\-]*/', '', $query) ?? $query;
        $variants[] = preg_replace('/\bTechnolgies\b/i', 'Technologies', $query) ?? $query;

        if (stripos($query, 'Chinni') !== false || stripos($query, 'Goldwin') !== false || stripos($query, '641062') !== false) {
            $variants[] = 'Chinniyampalayam Coimbatore 641062';
            $variants[] = 'Goldwins Chinniyampalayam Coimbatore';
            $variants[] = 'Civil Aerodrome Coimbatore 641062';
        }
        if (stripos($query, 'Tex Park') !== false || stripos($query, 'Texpark') !== false || stripos($query, '641014') !== false) {
            $variants[] = 'Tex Park Road Nehru Nagar West Coimbatore 641014';
            $variants[] = 'Nehru Nagar West Coimbatore 641014';
            $variants[] = 'Texpark Coimbatore';
        }
        if (stripos($query, 'Nehru') !== false) {
            $variants[] = 'Nehru Nagar West Coimbatore';
            $variants[] = 'Nehru Nagar Coimbatore';
        }

        return array_values(array_unique(array_filter($variants, fn ($v) => strlen(trim($v)) >= 3)));
    }
}
