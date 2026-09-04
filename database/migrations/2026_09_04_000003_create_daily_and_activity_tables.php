<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('record_date');
            $table->time('wake_up_time')->nullable();
            $table->time('sleep_time')->nullable();
            $table->unsignedTinyInteger('sleep_quality')->nullable(); // 1 - 5 stars
            $table->unsignedTinyInteger('day_rating')->nullable(); // 1 - 5 stars
            $table->decimal('sleep_duration_hours', 4, 2)->nullable();
            $table->text('sleep_notes')->nullable();
            $table->text('day_summary')->nullable();
            $table->boolean('wake_up_prompt_dismissed')->default(false);
            $table->boolean('sleep_prompt_dismissed')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'record_date']);
        });

        Schema::create('activity_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('icon')->default('check-circle');
            $table->string('color')->default('#4f46e5');
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('activity_categories')->nullOnDelete();
            $table->string('title');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('activity_categories');
        Schema::dropIfExists('daily_records');
    }
};
