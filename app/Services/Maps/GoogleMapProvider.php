<?php

namespace App\Services\Maps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapProvider implements MapProviderInterface
{
    public function __construct(private readonly OsmMapProvider $fallback) {}

    public function searchPlaces(string $query): array
    {
        $key = (string) config('services.maps.google_key');
        if ($key === '') {
            return $this->fallback->searchPlaces($query);
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $query,
                'components' => 'country:IN',
                'key' => $key,
            ]);

            $results = collect($response->json('results') ?? [])
                ->map(function (array $result): array {
                    return [
                        'label' => $this->formatGoogleAddress($result),
                        'lat' => (float) data_get($result, 'geometry.location.lat', 0),
                        'lng' => (float) data_get($result, 'geometry.location.lng', 0),
                    ];
                })
                ->filter(fn (array $result): bool => $result['label'] !== '' && $result['lat'] !== 0.0 && $result['lng'] !== 0.0)
                ->values()
                ->all();

            return $results !== [] ? $results : $this->fallback->searchPlaces($query);
        } catch (\Throwable $exception) {
            Log::warning('Google geocoding failed: '.$exception->getMessage());

            return $this->fallback->searchPlaces($query);
        }
    }

    public function routeDistanceKm(array $waypoints): ?float
    {
        $points = array_values(array_filter($waypoints, fn ($w) => isset($w['lat'], $w['lng'])));
        if (count($points) < 2) {
            return null;
        }

        $key = (string) config('services.maps.google_key');
        if ($key === '') {
            return $this->fallback->routeDistanceKm($waypoints);
        }

        $coords = collect($points)
            ->map(fn ($w) => round((float) $w['lat'], 5).','.round((float) $w['lng'], 5))
            ->implode(';');

        $cacheKey = 'geo:google_route:v1:'.md5($coords);
        $cached = Cache::get($cacheKey);
        if (is_numeric($cached)) {
            return (float) $cached;
        }

        try {
            $origin = $points[0]['lat'].','.$points[0]['lng'];
            $destination = $points[count($points) - 1]['lat'].','.$points[count($points) - 1]['lng'];

            $params = [
                'origin' => $origin,
                'destination' => $destination,
                'mode' => 'driving',
                'key' => $key,
            ];

            if (count($points) > 2) {
                $intermediates = array_slice($points, 1, -1);
                $waypointsParam = collect($intermediates)
                    ->map(fn ($p) => $p['lat'].','.$p['lng'])
                    ->implode('|');
                $params['waypoints'] = $waypointsParam;
            }

            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/directions/json', $params);

            if ($response->successful() && $response->json('status') === 'OK') {
                $routes = $response->json('routes') ?? [];
                if (! empty($routes)) {
                    $legs = $routes[0]['legs'] ?? [];
                    $totalMeters = collect($legs)->sum(fn ($leg) => data_get($leg, 'distance.value', 0));
                    if ($totalMeters > 0) {
                        $km = round($totalMeters / 1000, 2);
                        Cache::put($cacheKey, $km, now()->addDays(7));

                        return $km;
                    }
                }
            }

            Log::warning('Google Directions API status: '.($response->json('status') ?? 'request failed'));

            return $this->fallback->routeDistanceKm($waypoints);
        } catch (\Throwable $exception) {
            Log::warning('Google Directions API failed: '.$exception->getMessage());

            return $this->fallback->routeDistanceKm($waypoints);
        }
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function formatGoogleAddress(array $result): string
    {
        $components = collect($result['address_components'] ?? []);
        $lookup = function (array $types) use ($components): ?string {
            return $components
                ->first(fn (array $component): bool => count(array_intersect($types, $component['types'] ?? [])) > 0)['long_name'] ?? null;
        };

        $parts = array_filter([
            trim(implode(' ', array_filter([
                $lookup(['street_number']),
                $lookup(['route']),
            ]))),
            $lookup(['premise', 'subpremise', 'point_of_interest', 'establishment']),
            $lookup(['neighborhood', 'sublocality', 'sublocality_level_1']),
            $lookup(['locality', 'administrative_area_level_2']),
            $lookup(['administrative_area_level_1']),
            $lookup(['postal_code']),
            $lookup(['country']),
        ]);

        return implode(', ', array_unique($parts)) ?: (string) ($result['formatted_address'] ?? '');
    }

    public function reverseGeocode(float $lat, float $lng): ?string
    {
        $key = (string) config('services.maps.google_key');
        if ($key === '') {
            return $this->fallback->reverseGeocode($lat, $lng);
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => "{$lat},{$lng}",
                'key' => $key,
            ]);

            $results = $response->json('results') ?? [];
            if (! empty($results)) {
                return $this->formatGoogleAddress($results[0]);
            }

            return $this->fallback->reverseGeocode($lat, $lng);
        } catch (\Throwable $e) {
            return $this->fallback->reverseGeocode($lat, $lng);
        }
    }
}
