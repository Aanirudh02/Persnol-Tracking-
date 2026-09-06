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
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('gst_amount', 12, 2)->default(0)->after('amount');
        });

        Schema::table('food_entries', function (Blueprint $table) {
            $table->decimal('gst_amount', 12, 2)->default(0)->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_entries', function (Blueprint $table) {
            $table->dropColumn('gst_amount');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('gst_amount');
        });
    }
};
