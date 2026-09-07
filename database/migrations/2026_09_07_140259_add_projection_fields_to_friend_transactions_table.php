<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('friend_transactions', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('friend_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('projection_kind')->nullable()->after('payment_method');
            $table->decimal('actual_paid_by_me', 12, 2)->nullable()->after('projection_kind');
            $table->decimal('actual_paid_by_friend', 12, 2)->nullable()->after('actual_paid_by_me');
            $table->decimal('net_amount', 12, 2)->nullable()->after('actual_paid_by_friend');
            $table->unique(['source_type', 'source_id']);
            $table->index(['user_id', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::table('friend_transactions', function (Blueprint $table) {
            $table->dropUnique('friend_transactions_source_type_source_id_unique');
            $table->dropIndex('friend_transactions_user_id_source_type_index');
            $table->dropColumn([
                'source_type',
                'source_id',
                'projection_kind',
                'actual_paid_by_me',
                'actual_paid_by_friend',
                'net_amount',
            ]);
        });
    }
};
