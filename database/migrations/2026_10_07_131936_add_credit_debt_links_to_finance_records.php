<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link expenses / personal expenses / incomes to the credit or debt they came from,
     * and link each credit payment to the record it was filed as. Previously only the
     * latest record was kept in credit_debts.linked_*_id (older links were overwritten),
     * which is what let a credit be expensed twice and edits overwrite partial amounts.
     */
    public function up(): void
    {
        foreach (['expenses', 'personal_expenses', 'incomes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('credit_debt_id')->nullable()->constrained('credit_debts')->nullOnDelete();
            });
        }

        Schema::table('credit_debt_payments', function (Blueprint $table) {
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->foreignId('personal_expense_id')->nullable()->constrained('personal_expenses')->nullOnDelete();
            $table->foreignId('income_id')->nullable()->constrained('incomes')->nullOnDelete();
        });

        $this->backfillRecordLinks();
        $this->backfillPaymentLinks();
    }

    public function down(): void
    {
        Schema::table('credit_debt_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
            $table->dropConstrainedForeignId('personal_expense_id');
            $table->dropConstrainedForeignId('income_id');
        });

        foreach (['expenses', 'personal_expenses', 'incomes'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('credit_debt_id');
            });
        }
    }

    private function backfillRecordLinks(): void
    {
        $credits = DB::table('credit_debts')->get(['id', 'user_id', 'linked_expense_id', 'linked_personal_expense_id', 'linked_income_id'])->keyBy('id');

        // 1. The single link column each credit kept
        foreach ($credits as $credit) {
            foreach (['expenses' => 'linked_expense_id', 'personal_expenses' => 'linked_personal_expense_id', 'incomes' => 'linked_income_id'] as $tableName => $column) {
                if ($credit->{$column}) {
                    DB::table($tableName)
                        ->where('id', $credit->{$column})
                        ->where('user_id', $credit->user_id)
                        ->whereNull('credit_debt_id')
                        ->update(['credit_debt_id' => $credit->id]);
                }
            }
        }

        // 2. Older links that were overwritten survive only in the text ("Linked to Credit #5", "Credit #5 closed")
        foreach (['expenses', 'personal_expenses', 'incomes'] as $tableName) {
            DB::table($tableName)
                ->whereNull('credit_debt_id')
                ->where(fn ($query) => $query->where('notes', 'like', '%Credit #%')
                    ->orWhere('notes', 'like', '%Debt #%')
                    ->orWhere('description', 'like', '%Credit #%')
                    ->orWhere('description', 'like', '%Debt #%'))
                ->orderBy('id')
                ->get(['id', 'user_id', 'notes', 'description'])
                ->each(function ($row) use ($credits, $tableName): void {
                    if (! preg_match('/(?:Credit|Debt) #(\d+)/', ($row->notes ?? '').' '.($row->description ?? ''), $matches)) {
                        return;
                    }

                    $credit = $credits->get((int) $matches[1]);
                    if ($credit && (int) $credit->user_id === (int) $row->user_id) {
                        DB::table($tableName)->where('id', $row->id)->update(['credit_debt_id' => $credit->id]);
                    }
                });
        }
    }

    private function backfillPaymentLinks(): void
    {
        $patterns = [
            'personal_expense_id' => ['personal_expenses', '/Filed as personal expense #(\d+)/i'],
            'expense_id' => ['expenses', '/Filed as expense #(\d+)/i'],
            'income_id' => ['incomes', '/Recorded as Income #(\d+)/i'],
        ];

        DB::table('credit_debt_payments')->orderBy('id')->get()->each(function ($payment) use ($patterns): void {
            foreach ($patterns as $column => [$tableName, $pattern]) {
                if (preg_match($pattern, (string) $payment->notes, $matches)) {
                    if (DB::table($tableName)->where('id', (int) $matches[1])->exists()) {
                        DB::table('credit_debt_payments')->where('id', $payment->id)->update([$column => (int) $matches[1]]);
                    }

                    return;
                }
            }

            // "Close in full" created the payment and its expense/income together
            if ($payment->notes === 'Closed in full') {
                foreach (['expenses' => 'expense_id', 'incomes' => 'income_id'] as $tableName => $column) {
                    $recordId = DB::table($tableName)
                        ->where('credit_debt_id', $payment->credit_debt_id)
                        ->where('description', 'like', '%#'.$payment->credit_debt_id.' closed%')
                        ->where('amount', $payment->amount)
                        ->value('id');

                    if ($recordId) {
                        DB::table('credit_debt_payments')->where('id', $payment->id)->update([$column => $recordId]);

                        return;
                    }
                }
            }
        });
    }
};
