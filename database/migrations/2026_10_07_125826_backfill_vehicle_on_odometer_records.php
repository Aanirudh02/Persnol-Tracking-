<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Odometer cycles are now tracked per vehicle, so attach legacy rows
     * without a vehicle to the owner's default (or oldest) vehicle.
     */
    public function up(): void
    {
        $userIds = DB::table('odometer_readings')->whereNull('vehicle_id')->distinct()->pluck('user_id')
            ->merge(DB::table('odometer_groups')->whereNull('vehicle_id')->distinct()->pluck('user_id'))
            ->unique();

        foreach ($userIds as $userId) {
            $vehicleId = DB::table('vehicles')->where('user_id', $userId)->where('is_default', true)->value('id')
                ?? DB::table('vehicles')->where('user_id', $userId)->orderBy('id')->value('id');

            if (! $vehicleId) {
                continue;
            }

            DB::table('odometer_readings')->where('user_id', $userId)->whereNull('vehicle_id')->update(['vehicle_id' => $vehicleId]);
            DB::table('odometer_groups')->where('user_id', $userId)->whereNull('vehicle_id')->update(['vehicle_id' => $vehicleId]);
        }
    }

    public function down(): void
    {
        // Data backfill only; nothing to reverse.
    }
};
