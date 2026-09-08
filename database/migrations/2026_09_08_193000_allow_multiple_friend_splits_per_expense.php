<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('friend_splits', function (Blueprint $table) {
            // Drop the single unique constraint on expense_id so an expense can have multiple friend splits
            $table->dropUnique(['expense_id']);
            // Allow multiple splits per expense, uniquely identifying per (expense, friend) pair
            $table->unique(['expense_id', 'friend_id']);
        });
    }

    public function down(): void
    {
        Schema::table('friend_splits', function (Blueprint $table) {
            $table->dropUnique(['expense_id', 'friend_id']);
            $table->unique('expense_id');
        });
    }
};
