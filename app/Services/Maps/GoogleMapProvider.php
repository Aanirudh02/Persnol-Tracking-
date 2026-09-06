<?php

namespace App\Services\Maps;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Geocoding API adapter with the no-key OSM fallback.
 */
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
                ->map(fn (array $result): array => [
                    'label' => (string) ($result['formatted_address'] ?? ''),
                    'lat' => (float) data_get($result, 'geometry.location.lat', 0),
                    'lng' => (float) data_get($result, 'geometry.location.lng', 0),
                ])
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
}
