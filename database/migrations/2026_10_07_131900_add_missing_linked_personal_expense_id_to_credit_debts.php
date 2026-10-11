<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * linked_personal_expense_id was added to 2026_09_30_000001 after that migration had
     * already run in production (commit bc4d34c), so production never got the column.
     * Add it wherever it is missing; no-op where it already exists.
     */
    public function up(): void
    {
        if (Schema::hasColumn('credit_debts', 'linked_personal_expense_id')) {
            return;
        }

        Schema::table('credit_debts', function (Blueprint $table) {
            $table->foreignId('linked_personal_expense_id')->nullable()->constrained('personal_expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Owned by 2026_09_30_000001 where it exists; nothing to roll back here.
    }
};
