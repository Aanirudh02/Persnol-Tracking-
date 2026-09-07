<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expense categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('icon')->default('shopping-bag');
            $table->string('color')->default('#ef4444');
            $table->timestamps();
        });

        // Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('description');
            $table->string('payment_method')->default('UPI'); // Cash, UPI, Bank, Card, Other
            $table->string('paid_by')->default('Me'); // Me, or friend name
            $table->string('friend_person')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_image')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'date']);
        });

        // Income categories
        Schema::create('income_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('icon')->default('cash');
            $table->string('color')->default('#10b981');
            $table->timestamps();
        });

        // Income
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('income_categories')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('source'); // Salary, Pocket Money, Friend Returned, Transfer, Other
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('payment_method')->default('Bank');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'date']);
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('paid_by')->default('Me');
            $table->string('paid_to');
            $table->string('purpose');
            $table->string('category')->nullable();
            $table->string('payment_method')->default('UPI');
            $table->string('reference')->nullable();
            $table->enum('status', ['Pending', 'Reconciled', 'Cancelled', 'Disputed'])->default('Pending');
            $table->text('notes')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'date']);
            $table->index('status');
        });

        // Payment Reconciliations
        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('reconciled_date');
            $table->decimal('reconciled_amount', 12, 2);
            $table->string('reconciled_by');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Friends
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        // Friend Transactions & Shared Expenses
        Schema::create('friend_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'paid_for_friend',   // I paid, friend owes me
                'friend_paid_for_me', // Friend paid, I owe friend
                'shared_expense',     // Shared total, split shares
            ]);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('my_share', 12, 2);
            $table->decimal('friend_share', 12, 2);
            $table->string('description');
            $table->date('date');
            $table->string('payment_method')->default('UPI');
            $table->boolean('is_settled')->default(false);
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'friend_id']);
        });

        // Settlements
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('direction', ['i_paid_friend', 'friend_paid_me']);
            $table->date('date');
            $table->string('payment_method')->default('UPI');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('friend_transactions');
        Schema::dropIfExists('friends');
        Schema::dropIfExists('payment_reconciliations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('income_categories');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
