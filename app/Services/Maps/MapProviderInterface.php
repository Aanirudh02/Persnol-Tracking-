<?php

namespace App\Services\Maps;

interface MapProviderInterface
{
    /**
     * @return array<int, array{label: string, lat: float, lng: float}>
     */
    public function searchPlaces(string $query): array;

    /**
     * @param  array<int, array{lat: float, lng: float}>  $waypoints
     */
    public function routeDistanceKm(array $waypoints): ?float;

    public function reverseGeocode(float $lat, float $lng): ?string;
}
