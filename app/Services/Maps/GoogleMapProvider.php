<?php

namespace App\Services\Maps;

/**
 * Stub for a future Google Places + Distance Matrix provider.
 * Wire GOOGLE_MAPS_API_KEY and MAP_PROVIDER=google when ready.
 */
class GoogleMapProvider implements MapProviderInterface
{
    public function searchPlaces(string $query): array
    {
        return [];
    }

    public function routeDistanceKm(array $waypoints): ?float
    {
        return null;
    }
}
