<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_expense_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('personal_expenses', function (Blueprint $table) {
            $table->foreignId('personal_expense_group_id')
                ->nullable()
                ->after('expense_id')
                ->constrained('personal_expense_groups')
                ->nullOnDelete();
            $table->string('done_by')->default('Me')->after('payment_method');
            $table->string('done_to')->nullable()->after('done_by');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('is_locked');
            $table->index(['user_id', 'is_archived', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_archived', 'date']);
            $table->dropColumn('is_archived');
        });

        Schema::table('personal_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_expense_group_id');
            $table->dropColumn(['done_by', 'done_to']);
        });

        Schema::dropIfExists('personal_expense_groups');
    }
};
