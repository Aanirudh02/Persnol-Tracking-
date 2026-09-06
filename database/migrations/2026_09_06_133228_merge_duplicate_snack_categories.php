<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $canonical = DB::table('food_categories')
            ->whereNull('user_id')
            ->whereRaw('LOWER(name) = ?', ['snacks'])
            ->orderBy('id')
            ->first();

        if (! $canonical) {
            return;
        }

        $duplicateIds = DB::table('food_categories')
            ->whereNotNull('user_id')
            ->whereRaw('LOWER(name) = ?', ['snacks'])
            ->pluck('id');

        if ($duplicateIds->isEmpty()) {
            return;
        }

        DB::table('food_entries')
            ->whereIn('category_id', $duplicateIds)
            ->update(['category_id' => $canonical->id]);

        DB::table('food_categories')->whereIn('id', $duplicateIds)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data merges are intentionally not reversed.
    }
};
