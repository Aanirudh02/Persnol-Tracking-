<?php

namespace App\Services\Maps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsmMapProvider implements MapProviderInterface
{
    public function searchPlaces(string $query): array
    {
        $query = $this->normalizeQuery($query);
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = 'geo:search:v5:'.md5(mb_strtolower($query));
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        // Layer 1: Dynamic Trip History (auto-remembers any place user previously entered/visited)
        $historyResults = $this->searchHistoryPlaces($query);

        // Layer 2: Real-time Live OSM Search (Photon + Nominatim)
        $liveResults = array_merge(
            $this->searchPhoton($query),
            $this->searchNominatim($query),
            $this->localLandmarks($query)
        );

        $merged = array_merge($historyResults, $liveResults);
        $results = $this->rankResults($query, $merged);

        // Layer 3: Dynamic NLP Token Breakdown if exact phrase gave no hits
        if ($results === []) {
            foreach ($this->queryVariants($query) as $variant) {
                $variantResults = array_merge(
                    $this->searchHistoryPlaces($variant),
                    $this->searchPhoton($variant),
                    $this->searchNominatim($variant),
                    $this->localLandmarks($variant)
                );
                $results = $this->rankResults($variant, $variantResults);
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

    /**
     * Dynamically finds places previously logged in scooter trips matching the query.
     *
     * @return array<int, array{label: string, lat: float, lng: float}>
     */
    protected function searchHistoryPlaces(string $query): array
    {
        try {
            $term = trim($query);
            if (strlen($term) < 2) {
                return [];
            }

            $rows = DB::table('scooter_trips')
                ->select(['from_label', 'start_address', 'start_latitude', 'start_longitude', 'to_label', 'end_address', 'end_latitude', 'end_longitude', 'stops'])
                ->where(function ($q) use ($term) {
                    $q->where('from_label', 'LIKE', "%{$term}%")
                        ->orWhere('to_label', 'LIKE', "%{$term}%")
                        ->orWhere('start_address', 'LIKE', "%{$term}%")
                        ->orWhere('end_address', 'LIKE', "%{$term}%")
                        ->orWhere('stops', 'LIKE', "%{$term}%");
                })
                ->limit(20)
                ->get();

            $results = [];
            foreach ($rows as $r) {
                if (! empty($r->from_label) && $r->start_latitude && $r->start_longitude && stripos((string) $r->from_label, $term) !== false) {
                    $results[] = [
                        'label' => (string) $r->from_label,
                        'lat' => (float) $r->start_latitude,
                        'lng' => (float) $r->start_longitude,
                    ];
                }
                if (! empty($r->to_label) && $r->end_latitude && $r->end_longitude && stripos((string) $r->to_label, $term) !== false) {
                    $results[] = [
                        'label' => (string) $r->to_label,
                        'lat' => (float) $r->end_latitude,
                        'lng' => (float) $r->end_longitude,
                    ];
                }
                if (! empty($r->stops)) {
                    $stops = json_decode((string) $r->stops, true);
                    if (is_array($stops)) {
                        foreach ($stops as $s) {
                            if (! empty($s['label']) && ! empty($s['lat']) && ! empty($s['lng']) && stripos((string) $s['label'], $term) !== false) {
                                $results[] = [
                                    'label' => (string) $s['label'],
                                    'lat' => (float) $s['lat'],
                                    'lng' => (float) $s['lng'],
                                ];
                            }
                        }
                    }
                }
            }

            return collect($results)
                ->unique(fn ($row) => round($row['lat'], 4).','.round($row['lng'], 4))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
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

        // 1. Dr. NGP College / Institutions
        if (str_contains($q, 'ngp') || str_contains($q, 'n.g.p') || (str_contains($q, 'dr ngp') || str_contains($q, 'dr.ngp'))) {
            $hits[] = [
                'label' => 'Dr. N.G.P. Arts and Science College, Kalapatti Road, Coimbatore, Tamil Nadu 641048',
                'lat' => 11.05580,
                'lng' => 77.03750,
            ];
            $hits[] = [
                'label' => 'Dr. N.G.P. Institute of Technology, Kalapatti Road, Coimbatore, Tamil Nadu 641048',
                'lat' => 11.05700,
                'lng' => 77.03600,
            ];
            $hits[] = [
                'label' => 'Dr. N.G.P. Research Center / Conference Center, Kalapatti Road, Coimbatore 641048',
                'lat' => 11.05650,
                'lng' => 77.03700,
            ];
        }

        // 2. Kalapatti (and common spelling variations like kalapati)
        if (str_contains($q, 'kalapat') || str_contains($q, 'kalapatti')) {
            $hits[] = [
                'label' => 'Kalapatti, Coimbatore, Tamil Nadu 641048',
                'lat' => 11.07180,
                'lng' => 77.03540,
            ];
            $hits[] = [
                'label' => 'Kalapatti Main Road, Civil Aerodrome Post, Coimbatore 641014',
                'lat' => 11.04500,
                'lng' => 77.03100,
            ];
            $hits[] = [
                'label' => 'Kalapatti Four Roads / Junction, Coimbatore, Tamil Nadu 641048',
                'lat' => 11.07450,
                'lng' => 77.03700,
            ];
        }

        // 3. Mahil Pharmacy / Medicals
        if (str_contains($q, 'mahil')) {
            $hits[] = [
                'label' => 'Mahil Pharmacy & Medicals, Kalapatti Main Road, Nehru Nagar, Coimbatore 641014',
                'lat' => 11.04250,
                'lng' => 77.03200,
            ];
            $hits[] = [
                'label' => 'Mahil Clinic & Pharmacy, SITRA to Kalapatti Road, Coimbatore 641014',
                'lat' => 11.03850,
                'lng' => 77.03450,
            ];
        }

        // 4. Nehru Nagar / Nehru Nagar West / East
        if (str_contains($q, 'nehru') || str_contains($q, '641014')) {
            $hits[] = [
                'label' => 'Nehru Nagar West, Kalapatti Road, Coimbatore, Tamil Nadu 641014',
                'lat' => 11.04400,
                'lng' => 77.02700,
            ];
            $hits[] = [
                'label' => 'Nehru Nagar East, Kalapatti Main Road, Coimbatore, Tamil Nadu 641014',
                'lat' => 11.04350,
                'lng' => 77.03150,
            ];
            $hits[] = [
                'label' => '414-A Tex Park Road, Nehru Nagar West, Coimbatore, Tamil Nadu 641014',
                'lat' => 11.01855,
                'lng' => 77.02685,
            ];
            $hits[] = [
                'label' => 'IMIK Technologies, Nehru Nagar, Coimbatore, Tamil Nadu 641014',
                'lat' => 11.01855,
                'lng' => 77.02685,
            ];
        }

        // 5. Goldwins / Chinniyampalayam
        $isGoldwins = str_contains($q, 'goldwin')
            || (str_contains($q, 'chinni') && (str_contains($q, '641062') || str_contains($q, 'aerodrome') || str_contains($q, 'civil')));
        if ($isGoldwins || str_contains($q, 'chinniyampalayam')) {
            $hits[] = [
                'label' => '112 Goldwins, Civil Aerodrome Post, Chinniyampalayam, Coimbatore, Tamil Nadu 641062',
                'lat' => 11.03085,
                'lng' => 77.04295,
            ];
            $hits[] = [
                'label' => 'Chinniyampalayam, Avinashi Road, Coimbatore, Tamil Nadu 641062',
                'lat' => 11.03250,
                'lng' => 77.05100,
            ];
            $hits[] = [
                'label' => 'DMart Chinniyampalayam, Avinashi Road, Coimbatore 641062',
                'lat' => 11.03400,
                'lng' => 77.04900,
            ];
        }

        // 6. SITRA / Airport
        if (str_contains($q, 'sitra') || str_contains($q, 'airport')) {
            $hits[] = [
                'label' => 'SITRA Junction, Avinashi Road, Civil Aerodrome Post, Coimbatore 641014',
                'lat' => 11.03450,
                'lng' => 77.03900,
            ];
            $hits[] = [
                'label' => 'Coimbatore International Airport (CJB), Peelamedu, Coimbatore 641014',
                'lat' => 11.02980,
                'lng' => 77.04340,
            ];
        }

        // 7. KMCH / Hospitals
        if (str_contains($q, 'kmch') || str_contains($q, 'kovai medical')) {
            $hits[] = [
                'label' => 'Kovai Medical Center and Hospital (KMCH), Avinashi Road, Coimbatore 641014',
                'lat' => 11.03350,
                'lng' => 77.03200,
            ];
        }

        // 8. Hope College / Peelamedu / Gandhipuram
        if (str_contains($q, 'hope college') || str_contains($q, 'hopecollege')) {
            $hits[] = [
                'label' => 'Hope College, Avinashi Road, Peelamedu, Coimbatore 641004',
                'lat' => 11.02550,
                'lng' => 77.00550,
            ];
        }
        if (str_contains($q, 'gandhipuram')) {
            $hits[] = [
                'label' => 'Gandhipuram Central Bus Stand, Coimbatore, Tamil Nadu 641012',
                'lat' => 11.01800,
                'lng' => 76.96700,
            ];
        }
        if (str_contains($q, 'peelamedu')) {
            $hits[] = [
                'label' => 'Peelamedu, Coimbatore, Tamil Nadu 641004',
                'lat' => 11.02700,
                'lng' => 77.01500,
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
                if (str_contains($q, 'dmart') || str_contains($q, 'd mart')) {
                    if (str_contains($label, 'dmart') || str_contains($label, 'd mart')) {
                        $score += 180;
                    }
                    if (str_contains($label, 'coimbatore') || str_contains($label, 'chinniyampalayam')) {
                        $score += 80;
                    }
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
                    'label' => $this->formatNominatimLabel($row),
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
        $q = trim($query);

        // 1. Generic token & punctuation decomposition
        $clean = preg_replace('/[,\-\/]+/', ' ', $q) ?? $q;
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? '');

        // Suffix with regional context
        $variants[] = $clean.' Coimbatore';
        $variants[] = $clean.' Tamil Nadu';

        // Comma-separated parts (e.g. "Coffee Meet, Kalapatti")
        $commaParts = array_values(array_filter(array_map('trim', explode(',', $q))));
        if (count($commaParts) >= 2) {
            $first = $commaParts[0];
            $second = $commaParts[1];
            $variants[] = $first.' '.$second.' Coimbatore';
            $variants[] = $first.' Coimbatore';
            $variants[] = $second.' Coimbatore';
        }

        // Multi-word slices
        $words = explode(' ', $clean);
        if (count($words) >= 3) {
            $variants[] = implode(' ', array_slice($words, 0, 2)).' Coimbatore';
            $variants[] = implode(' ', array_slice($words, 0, 2)).' '.end($words);
            $variants[] = implode(' ', array_slice($words, -2)).' Coimbatore';
        } elseif (count($words) === 2) {
            $variants[] = $words[0].' '.$words[1].' Coimbatore';
            $variants[] = $words[0].' Coimbatore';
            $variants[] = $words[1].' Coimbatore';
        }

        // Remove filler prepositions ("near", "opp", "opposite", "beside", "behind", "next to")
        $withoutFillers = preg_replace('/\b(near|opp|opposite|beside|behind|next to)\b/i', '', $clean);
        $withoutFillers = trim(preg_replace('/\s+/', ' ', $withoutFillers ?? '') ?? '');
        if ($withoutFillers !== '' && $withoutFillers !== $clean) {
            $variants[] = $withoutFillers;
            $variants[] = $withoutFillers.' Coimbatore';
        }

        // Specific high-frequency shorthands
        if (stripos($query, 'dmart') !== false || stripos($query, 'd mart') !== false) {
            $variants[] = 'DMart Chinniyampalayam Coimbatore Tamil Nadu';
            $variants[] = 'DMart Coimbatore Tamil Nadu';
        }
        if (stripos($query, 'Chinni') !== false || stripos($query, 'Goldwin') !== false || stripos($query, '641062') !== false) {
            $variants[] = 'Chinniyampalayam Coimbatore 641062';
            $variants[] = 'Goldwins Chinniyampalayam Coimbatore';
        }
        if (stripos($query, 'Tex Park') !== false || stripos($query, 'Texpark') !== false || stripos($query, '641014') !== false) {
            $variants[] = 'Tex Park Road Nehru Nagar West Coimbatore 641014';
            $variants[] = 'Nehru Nagar West Coimbatore 641014';
        }
        if (stripos($query, 'ngp') !== false || stripos($query, 'n.g.p') !== false) {
            $variants[] = 'Dr NGP College Kalapatti Road Coimbatore';
            $variants[] = 'Dr NGP Institute of Technology Coimbatore';
        }
        if (stripos($query, 'kalapat') !== false) {
            $variants[] = 'Kalapatti Coimbatore';
            $variants[] = 'Kalapatti Main Road Coimbatore';
        }
        if (stripos($query, 'mahil') !== false) {
            $variants[] = 'Mahil Pharmacy Kalapatti Road Coimbatore';
        }

        return array_values(array_unique(array_filter($variants, fn ($v) => strlen(trim($v)) >= 3)));
    }

    public function reverseGeocode(float $lat, float $lng): ?string
    {
        $cacheKey = 'geo:reverse:'.round($lat, 4).','.round($lng, 4);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'LifeTracker/1.0 (personal scooter tracker; contact: local)',
                'Accept' => 'application/json',
            ])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $lat,
                    'lon' => $lng,
                    'addressdetails' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $label = $this->formatNominatimLabel($response->json() ?? []);
            if ($label !== '') {
                Cache::put($cacheKey, $label, now()->addDays(14));

                return $label;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('Reverse geocode failed: '.$e->getMessage());

            return null;
        }
    }

    protected function normalizeQuery(string $query): string
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? '');
        $query = preg_replace('/\bD\s*[-]?\s*Mart\b/i', 'DMart', $query) ?? $query;
        $query = preg_replace('/\bChinniampalayam\b/i', 'Chinniyampalayam', $query) ?? $query;
        $query = preg_replace('/\bKalapati\b/i', 'Kalapatti', $query) ?? $query;
        $query = preg_replace('/\bTamilnadu\b/i', 'Tamil Nadu', $query) ?? $query;

        return $query;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function formatNominatimLabel(array $row): string
    {
        $address = $row['address'] ?? [];
        $parts = array_filter([
            trim(implode(' ', array_filter([
                $address['house_number'] ?? null,
                $address['road'] ?? null,
            ]))),
            $address['building'] ?? null,
            $address['neighbourhood'] ?? ($address['suburb'] ?? ($address['hamlet'] ?? null)),
            $address['city_district'] ?? null,
            $address['city'] ?? ($address['town'] ?? ($address['village'] ?? null)),
            $address['state'] ?? null,
            $address['postcode'] ?? null,
            $address['country'] ?? null,
        ]);

        return implode(', ', array_unique($parts)) ?: (string) ($row['display_name'] ?? '');
    }
}
