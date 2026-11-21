<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason', 30)->nullable();
            $table->boolean('is_unlogged')->default(false);
        });

        Schema::table('personal_expenses', function (Blueprint $table) {
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason', 30)->nullable();
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->boolean('keep_separate')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn('keep_separate');
        });

        Schema::table('personal_expenses', function (Blueprint $table) {
            $table->dropColumn(['is_closed', 'closed_at', 'closed_reason']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['is_closed', 'closed_at', 'closed_reason', 'is_unlogged']);
        });
    }
};
