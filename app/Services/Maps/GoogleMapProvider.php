<?php

namespace App\Services\Maps;

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
        return $this->fallback->routeDistanceKm($waypoints);
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
}
