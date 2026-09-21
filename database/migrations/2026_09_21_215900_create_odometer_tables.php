<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odometer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('status')->default('active'); // active, completed
            $table->foreignId('start_fuel_entry_id')->nullable()->constrained('fuel_entries')->nullOnDelete();
            $table->foreignId('end_fuel_entry_id')->nullable()->constrained('fuel_entries')->nullOnDelete();
            $table->decimal('start_odometer', 10, 2);
            $table->decimal('end_odometer', 10, 2)->nullable();
            $table->decimal('total_km', 10, 2)->nullable();
            $table->decimal('total_litres', 8, 2)->nullable();
            $table->decimal('total_fuel_cost', 10, 2)->nullable();
            $table->decimal('calculated_mileage', 8, 2)->nullable();
            $table->decimal('cost_per_km', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });

        Schema::create('odometer_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('odometer_group_id')->constrained('odometer_groups')->cascadeOnDelete();
            $table->string('reading_type'); // source, intermediate, ending
            $table->decimal('odometer_km', 10, 2);
            $table->date('reading_date');
            $table->time('reading_time')->nullable();
            $table->string('trip_name')->nullable();
            $table->string('source_location')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('avg_speed_kmh', 6, 2)->nullable();
            $table->string('odometer_image', 1000)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'reading_date']);
            $table->index(['odometer_group_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odometer_readings');
        Schema::dropIfExists('odometer_groups');
    }
};
