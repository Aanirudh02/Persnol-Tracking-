<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friend_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->unsignedBigInteger('legacy_friend_transaction_id')->nullable();
            $table->string('description');
            $table->date('date');
            $table->string('payment_method')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('my_share', 12, 2);
            $table->decimal('friend_share', 12, 2);
            $table->decimal('paid_by_me_amount', 12, 2);
            $table->decimal('paid_by_friend_amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique('expense_id');
            $table->unique('legacy_friend_transaction_id');
            $table->index(['user_id', 'friend_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friend_splits');
    }
};
