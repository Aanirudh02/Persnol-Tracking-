<?php

namespace App\Services;

use App\Models\FuelEntry;
use App\Models\Vehicle;

class MileageService
{
    public function recompute(Vehicle $vehicle): ?float
    {
        $fills = FuelEntry::where('vehicle_id', $vehicle->id)
            ->whereNotNull('odometer')
            ->where('odometer', '>', 0)
            ->whereNotNull('litres')
            ->where('litres', '>', 0)
            ->orderBy('odometer')
            ->get();

        if ($fills->count() < 2) {
            return null;
        }

        $samples = [];
        for ($i = 1; $i < $fills->count(); $i++) {
            $prev = $fills[$i - 1];
            $curr = $fills[$i];
            $km = (int) $curr->odometer - (int) $prev->odometer;
            $litres = (float) $curr->litres;
            if ($km > 0 && $litres > 0) {
                $samples[] = $km / $litres;
            }
        }

        if ($samples === []) {
            return null;
        }

        $avg = round(array_sum($samples) / count($samples), 2);
        $vehicle->update(['actual_mileage_kmpl' => $avg]);

        return $avg;
    }
}
