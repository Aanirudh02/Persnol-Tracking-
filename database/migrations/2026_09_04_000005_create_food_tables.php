<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('icon')->default('cup');
            $table->string('color')->default('#f59e0b');
            $table->timestamps();
        });

        Schema::create('food_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('food_categories')->nullOnDelete();
            $table->string('item_name');
            $table->boolean('is_snack')->default(false);
            $table->integer('quantity')->default(1);
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('location')->nullable();
            $table->string('paid_by')->default('Me');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'is_snack']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_entries');
        Schema::dropIfExists('food_categories');
    }
};
