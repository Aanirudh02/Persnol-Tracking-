<?php

namespace App\Console\Commands;

use App\Models\CreditDebt;
use App\Models\CreditDebtPayment;
use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairCreditDebtLinks extends Command
{
    protected $signature = 'finance:repair-credit-links {--fix : Apply the repairs (default is a dry run that only reports)}';

    protected $description = 'Find and repair money double-counted by closing a credit/debt, and linked amounts overwritten by credit edits';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $this->info($fix ? 'Repairing…' : 'Dry run — nothing is changed. Re-run with --fix to apply.');

        $duplicates = $this->repairDoubleCountedCloses($fix);
        $overwritten = $this->repairOverwrittenAmounts($fix);

        $this->newLine();
        $this->info("Double-counted closings: {$duplicates} · Overwritten linked amounts: {$overwritten}".($fix ? ' (repaired)' : ''));

        return self::SUCCESS;
    }

    /**
     * Credits whose linked expenses (or debts whose linked incomes) add up to more than
     * the amount, because "Close" filed the full remaining amount again.
     */
    private function repairDoubleCountedCloses(bool $fix): int
    {
        $found = 0;

        CreditDebt::query()->with('friend')->orderBy('id')->each(function (CreditDebt $creditDebt) use ($fix, &$found): void {
            $limit = (float) $creditDebt->amount - (float) $creditDebt->settled_discount_amount;
            $isCredit = $creditDebt->type === 'credit';
            $recorded = $isCredit ? $creditDebt->expensedAmount() : $creditDebt->incomeRecordedAmount();
            $excess = round($recorded - $limit, 2);

            if ($excess <= 0.01) {
                return;
            }

            // The extra record is the one "Close" created: "... (Credit #12 closed)"
            $closingRecords = ($isCredit ? $creditDebt->linkedExpenses() : $creditDebt->linkedIncomes())
                ->where('description', 'like', '%#'.$creditDebt->id.' closed%')
                ->orderByDesc('id')
                ->get();

            $found++;
            $this->line(sprintf(
                '%s #%d (%s): ₹%s recorded as %s against ₹%s — ₹%s too much.',
                ucfirst($creditDebt->type),
                $creditDebt->id,
                $creditDebt->friend?->name ?? 'Friend',
                number_format($recorded, 2),
                $isCredit ? 'expense' : 'income',
                number_format($limit, 2),
                number_format($excess, 2)
            ));

            if (! $fix) {
                return;
            }

            DB::transaction(function () use ($closingRecords, $excess, $creditDebt): void {
                $toRemove = $excess;
                foreach ($closingRecords as $record) {
                    if ($toRemove <= 0.01) {
                        break;
                    }

                    $recordAmount = $record instanceof Expense ? $record->totalAmount() : (float) $record->amount;
                    if ($recordAmount - $toRemove <= 0.01) {
                        // Whole record is the duplicate: soft delete (restorable from the archive/trash)
                        CreditDebtPayment::where('credit_debt_id', $creditDebt->id)
                            ->where($record instanceof Expense ? 'expense_id' : 'income_id', $record->id)
                            ->update([$record instanceof Expense ? 'expense_id' : 'income_id' => null]);
                        $record->update(['notes' => trim(($record->notes ?? '')."\nRemoved by finance:repair-credit-links (duplicate of an earlier record).")]);
                        $record->delete();
                        $toRemove = round($toRemove - $recordAmount, 2);
                    } else {
                        // Only part of it was duplicated: reduce it
                        $record->update(['amount' => round((float) $record->amount - $toRemove, 2)]);
                        $toRemove = 0;
                    }
                }

                if ($creditDebt->linked_expense_id && Expense::find($creditDebt->linked_expense_id) === null) {
                    $creditDebt->update(['linked_expense_id' => $creditDebt->linkedExpenses()->latest('id')->value('id')]);
                }
            });

            $this->line('  → repaired');
        });

        return $found;
    }

    /**
     * Records filed for one payment whose amount was later overwritten with the credit's
     * full amount by editing the credit (they should equal their payment).
     */
    private function repairOverwrittenAmounts(bool $fix): int
    {
        $found = 0;

        CreditDebtPayment::query()
            ->with(['creditDebt', 'expense', 'personalExpense', 'income'])
            ->where(fn ($q) => $q->whereNotNull('expense_id')->orWhereNotNull('personal_expense_id')->orWhereNotNull('income_id'))
            ->orderBy('id')
            ->each(function (CreditDebtPayment $payment) use ($fix, &$found): void {
                $record = $payment->expense ?? $payment->personalExpense ?? $payment->income;
                if (! $record || ! $payment->creditDebt) {
                    return;
                }

                $recordAmount = $record instanceof Expense ? $record->totalAmount() : round((float) $record->amount, 2);
                $paymentAmount = round((float) $payment->amount, 2);
                $creditAmount = round((float) $payment->creditDebt->amount, 2);

                // Signature of the old bug: record now equals the credit total, not its own payment
                if (abs($recordAmount - $paymentAmount) < 0.01 || abs($recordAmount - $creditAmount) >= 0.01) {
                    return;
                }

                $found++;
                $this->line(sprintf(
                    '%s #%d (from %s #%d): shows ₹%s but its payment was ₹%s.',
                    class_basename($record),
                    $record->id,
                    ucfirst($payment->creditDebt->type),
                    $payment->creditDebt->id,
                    number_format($recordAmount, 2),
                    number_format($paymentAmount, 2)
                ));

                if ($fix) {
                    $record->update([
                        'amount' => $record instanceof Expense
                            ? round($paymentAmount - (float) $record->gst_amount, 2)
                            : $paymentAmount,
                    ]);
                    $this->line('  → repaired');
                }
            });

        return $found;
    }
}
