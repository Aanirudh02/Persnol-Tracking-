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
        Schema::table('daily_balances', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_balances', 'categories_data')) {
                $table->json('categories_data')->nullable()->after('closing_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_balances', function (Blueprint $table) {
            if (Schema::hasColumn('daily_balances', 'categories_data')) {
                $table->dropColumn('categories_data');
            }
        });
    }
};
