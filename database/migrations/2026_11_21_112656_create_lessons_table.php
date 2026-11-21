<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mistake_id')->nullable()->constrained('mistakes')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('mistake_categories')->nullOnDelete();
            $table->date('date');
            $table->string('title');
            $table->text('lesson');
            $table->string('tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
