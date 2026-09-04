<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mistake_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('color')->default('#ef4444');
            $table->timestamps();
        });

        Schema::create('mistakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('mistake_categories')->nullOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('title');
            $table->text('what_happened')->nullable();
            $table->text('why_happened')->nullable();
            $table->text('what_should_have_done')->nullable();
            $table->text('lesson_learned')->nullable();
            $table->text('prevention_plan')->nullable();
            $table->enum('severity', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->enum('status', ['Open', 'Working On It', 'Resolved', 'Learned'])->default('Open');
            $table->string('tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->date('date');
            $table->string('tags')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
        Schema::dropIfExists('mistakes');
        Schema::dropIfExists('mistake_categories');
    }
};
