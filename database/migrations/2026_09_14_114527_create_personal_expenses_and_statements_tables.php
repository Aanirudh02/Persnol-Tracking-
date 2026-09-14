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
        Schema::create('personal_expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('personal_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('personal_expense_categories')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('description')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_voluntary')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expense_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('normal'); // 'normal' or 'personal'
            $table->string('period_type')->default('all'); // 'all', 'day', 'week', 'month', 'year', 'custom'
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('category_ids')->nullable();
            $table->json('custom_expense_ids')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_statements');
        Schema::dropIfExists('personal_expenses');
        Schema::dropIfExists('personal_expense_categories');
    }
};
