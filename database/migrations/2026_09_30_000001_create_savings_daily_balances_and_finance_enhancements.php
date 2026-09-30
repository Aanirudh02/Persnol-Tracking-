<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Savings Table
        if (! Schema::hasTable('savings')) {
            Schema::create('savings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('source')->default('Manual');
                $table->string('goal_or_category')->nullable();
                $table->date('saved_date');
                $table->text('notes')->nullable();
                $table->string('status', 30)->default('active'); // active, locked, withdrawn
                $table->decimal('withdrawn_amount', 12, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['user_id', 'saved_date']);
            });
        }

        // 2. Income - Expense Tallies Table
        if (! Schema::hasTable('income_expense_tallies')) {
            Schema::create('income_expense_tallies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('income_id')->constrained('incomes')->cascadeOnDelete();
                $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
                $table->decimal('allocated_amount', 12, 2);
                $table->string('notes')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'income_id']);
                $table->index(['user_id', 'expense_id']);
            });
        }

        // 3. Daily Balances & Cash Register Table
        if (! Schema::hasTable('daily_balances')) {
            Schema::create('daily_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('record_date');
                $table->decimal('opening_balance', 12, 2)->default(0);
                $table->decimal('manual_adjustment', 12, 2)->default(0);
                $table->decimal('closing_balance', 12, 2)->default(0);
                $table->boolean('is_opening_manual')->default(false);
                $table->boolean('is_closing_manual')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'record_date']);
            });
        }

        // 4. Add classification to expenses
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'classification')) {
                $table->string('classification', 30)->default('necessary')->after('is_voluntary');
            }
        });

        // 5. Add classification to personal_expenses
        Schema::table('personal_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('personal_expenses', 'classification')) {
                $table->string('classification', 30)->default('necessary')->after('notes');
            }
        });

        // 6. Add settlement and link fields to credit_debts
        Schema::table('credit_debts', function (Blueprint $table) {
            if (! Schema::hasColumn('credit_debts', 'settled_discount_amount')) {
                $table->decimal('settled_discount_amount', 12, 2)->default(0)->after('amount_paid');
            }
            if (! Schema::hasColumn('credit_debts', 'is_settled_discounted')) {
                $table->boolean('is_settled_discounted')->default(false)->after('settled_discount_amount');
            }
            if (! Schema::hasColumn('credit_debts', 'linked_expense_id')) {
                $table->foreignId('linked_expense_id')->nullable()->after('is_settled_discounted')->constrained('expenses')->nullOnDelete();
            }
            if (! Schema::hasColumn('credit_debts', 'linked_income_id')) {
                $table->foreignId('linked_income_id')->nullable()->after('linked_expense_id')->constrained('incomes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('credit_debts', function (Blueprint $table) {
            if (Schema::hasColumn('credit_debts', 'linked_income_id')) {
                $table->dropForeign(['linked_income_id']);
                $table->dropColumn('linked_income_id');
            }
            if (Schema::hasColumn('credit_debts', 'linked_expense_id')) {
                $table->dropForeign(['linked_expense_id']);
                $table->dropColumn('linked_expense_id');
            }
            if (Schema::hasColumn('credit_debts', 'is_settled_discounted')) {
                $table->dropColumn('is_settled_discounted');
            }
            if (Schema::hasColumn('credit_debts', 'settled_discount_amount')) {
                $table->dropColumn('settled_discount_amount');
            }
        });

        Schema::table('personal_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('personal_expenses', 'classification')) {
                $table->dropColumn('classification');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'classification')) {
                $table->dropColumn('classification');
            }
        });

        Schema::dropIfExists('daily_balances');
        Schema::dropIfExists('income_expense_tallies');
        Schema::dropIfExists('savings');
    }
};
