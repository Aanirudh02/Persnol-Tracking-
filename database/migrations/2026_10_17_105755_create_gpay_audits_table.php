<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gpay_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('screenshot_path')->nullable();
            $table->string('month_year', 10)->nullable();
            $table->longText('raw_text')->nullable();
            $table->json('extracted_items')->nullable();
            $table->json('audit_results')->nullable();
            $table->decimal('gpay_balance', 12, 2)->nullable();
            $table->string('status', 30)->default('audited');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gpay_audits');
    }
};
