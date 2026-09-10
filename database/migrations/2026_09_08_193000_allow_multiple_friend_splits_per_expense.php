<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('friend_splits', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropUnique(['expense_id']);
            $table->foreign('expense_id')->references('id')->on('expenses')->nullOnDelete();
            $table->unique(['expense_id', 'friend_id']);
        });
    }

    public function down(): void
    {
        Schema::table('friend_splits', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropUnique(['expense_id', 'friend_id']);
            $table->unique('expense_id');
            $table->foreign('expense_id')->references('id')->on('expenses')->nullOnDelete();
        });
    }
};
