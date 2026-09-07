<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scooter_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->date('date');

            // Start
            $table->time('start_time')->nullable();
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            $table->string('start_address')->nullable();

            // End
            $table->time('end_time')->nullable();
            $table->decimal('end_latitude', 10, 7)->nullable();
            $table->decimal('end_longitude', 10, 7)->nullable();
            $table->string('end_address')->nullable();

            // Metrics
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('speedometer_image')->nullable();
            $table->integer('odometer_reading')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['ongoing', 'completed'])->default('completed');
            $table->timestamps();
            $table->index(['user_id', 'date']);
        });

        Schema::create('fuel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_record_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('litres', 8, 2);
            $table->decimal('price_per_litre', 8, 2);
            $table->integer('odometer')->nullable();
            $table->string('petrol_station')->nullable();
            $table->string('payment_method')->default('UPI');
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_entries');
        Schema::dropIfExists('scooter_trips');
    }
};
