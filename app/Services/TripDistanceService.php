<?php

namespace App\Services;

use App\Models\FuelEntry;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Services\Maps\MapProviderInterface;

class TripDistanceService
{
    public function __construct(protected MapProviderInterface $maps) {}

    /**
     * @param  array{lat?: float, lng?: float}|null  $start
     * @param  array{lat?: float, lng?: float}|null  $end
     * @param  array<int, array{lat?: float, lng?: float}>  $stops
     * @return array{one_way_km: float|null, distance_km: float|null, distance_source: string|null, estimated_litres: float|null, estimated_fuel_cost: float|null}
     */
    public function calculate(
        ?array $start,
        ?array $end,
        array $stops = [],
        bool $toAndFro = false,
        ?Vehicle $vehicle = null,
        ?int $userId = null
    ): array {
        $waypoints = [];
        if ($start && isset($start['lat'], $start['lng'])) {
            $waypoints[] = $start;
        }
        foreach ($stops as $stop) {
            if (isset($stop['lat'], $stop['lng'])) {
                $waypoints[] = $stop;
            }
        }
        if ($end && isset($end['lat'], $end['lng'])) {
            $waypoints[] = $end;
        }

        $oneWay = null;
        $source = null;

        if (count($waypoints) >= 2) {
            $oneWay = $this->maps->routeDistanceKm($waypoints);
            $source = $oneWay !== null ? 'osrm' : null;

            if ($oneWay === null) {
                $oneWay = $this->haversinePath($waypoints);
                if ($oneWay !== null) {
                    $oneWay = round($oneWay * 1.3, 2);
                    $source = 'haversine';
                }
            }
        }

        $distance = $oneWay;
        if ($distance !== null && $toAndFro) {
            $distance = round($distance * 2, 2);
        }

        $litres = null;
        $cost = null;
        if ($distance !== null && $vehicle) {
            $mileage = $vehicle->effectiveMileage();
            if ($mileage > 0) {
                $litres = round($distance / $mileage, 3);
                $price = $this->latestFuelPrice($userId ?? $vehicle->user_id, $vehicle->id);
                if ($price) {
                    $cost = round($litres * $price, 2);
                }
            }
        }

        return [
            'one_way_km' => $oneWay,
            'distance_km' => $distance,
            'distance_source' => $source,
            'estimated_litres' => $litres,
            'estimated_fuel_cost' => $cost,
        ];
    }

    protected function haversinePath(array $waypoints): ?float
    {
        $total = 0.0;
        for ($i = 1; $i < count($waypoints); $i++) {
            $a = $waypoints[$i - 1];
            $b = $waypoints[$i];
            $seg = $this->haversine($a['lat'], $a['lng'], $b['lat'], $b['lng']);
            if ($seg === null) {
                return null;
            }
            $total += $seg;
        }

        return round($total, 2);
    }

    protected function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    protected function latestFuelPrice(int $userId, ?int $vehicleId = null): ?float
    {
        $q = FuelEntry::where('user_id', $userId)->whereNotNull('price_per_litre')->where('price_per_litre', '>', 0);
        if ($vehicleId) {
            $q->where(function ($inner) use ($vehicleId) {
                $inner->where('vehicle_id', $vehicleId)->orWhereNull('vehicle_id');
            });
        }
        $entry = $q->latest('date')->first();

        return $entry ? (float) $entry->price_per_litre : null;
    }
}
