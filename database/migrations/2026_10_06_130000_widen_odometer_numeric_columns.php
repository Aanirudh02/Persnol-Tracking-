<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odometer_readings', function (Blueprint $table) {
            $table->decimal('odometer_km', 12, 2)->change();
            $table->decimal('distance_km', 12, 2)->nullable()->change();
            $table->decimal('avg_speed_kmh', 10, 2)->nullable()->change();
        });

        Schema::table('odometer_groups', function (Blueprint $table) {
            $table->decimal('start_odometer', 12, 2)->change();
            $table->decimal('end_odometer', 12, 2)->nullable()->change();
            $table->decimal('total_km', 12, 2)->nullable()->change();
            $table->decimal('calculated_mileage', 10, 2)->nullable()->change();
            $table->decimal('cost_per_km', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('odometer_readings', function (Blueprint $table) {
            $table->decimal('odometer_km', 10, 2)->change();
            $table->decimal('distance_km', 8, 2)->nullable()->change();
            $table->decimal('avg_speed_kmh', 6, 2)->nullable()->change();
        });

        Schema::table('odometer_groups', function (Blueprint $table) {
            $table->decimal('start_odometer', 10, 2)->change();
            $table->decimal('end_odometer', 10, 2)->nullable()->change();
            $table->decimal('total_km', 10, 2)->nullable()->change();
            $table->decimal('calculated_mileage', 8, 2)->nullable()->change();
            $table->decimal('cost_per_km', 8, 2)->nullable()->change();
        });
    }
};
