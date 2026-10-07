<?php

namespace App\Services;

use App\Models\CreditDebt;
use App\Models\CreditDebtPayment;
use App\Models\Expense;
use App\Models\Income;
use App\Models\PersonalExpense;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Keeps a credit/debt and the expenses, personal expenses and incomes filed from it
 * in step — but only for the fields the user agreed to sync, and never by blindly
 * copying the credit's full amount onto records that covered a partial payment.
 */
class CreditDebtLinkService
{
    public const SYNCABLE_FIELDS = ['payment_method', 'date', 'amount'];

    public function __construct(protected FinanceLinkService $financeLinkService) {}

    /**
     * Records filed from a credit/debt, for the "also update linked records?" prompt.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function describeLinkedRecords(CreditDebt $creditDebt): array
    {
        return $this->linkedRecords($creditDebt)
            ->map(fn (Expense|PersonalExpense|Income $record) => [
                'label' => $this->recordLabel($record),
                'url' => $this->recordUrl($record),
            ])
            ->values()
            ->all();
    }

    /**
     * The credit/debt a record was filed from, for the reverse prompt.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public function describeCreditFor(Expense|PersonalExpense|Income $record): array
    {
        $creditDebt = $record->creditDebt;
        if (! $creditDebt) {
            return [];
        }

        return [[
            'label' => ucfirst($creditDebt->type).' #'.$creditDebt->id.' · '.($creditDebt->friend?->name ?? 'Friend')
                .' · ₹'.number_format((float) $creditDebt->amount, 2).' · '.($creditDebt->payment_method ?: 'No method'),
            'url' => route('credits.edit', $creditDebt),
        ]];
    }

    /**
     * Apply credit/debt edits to its linked records.
     *
     * @param  array{payment_method?: string|null, date?: string|null, amount?: float|string|null}  $before
     * @param  array<int, string>  $fieldsToSync
     * @return array<int, string> human readable summary
     */
    public function syncFromCreditDebt(CreditDebt $creditDebt, array $before, array $fieldsToSync): array
    {
        $summary = [];
        $after = $this->creditValues($creditDebt);
        $changed = $this->changedFields($before, $after, $fieldsToSync);

        if ($changed === []) {
            return $summary;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($this->linkedRecords($creditDebt) as $record) {
            $changes = [];

            if (in_array('payment_method', $changed, true)) {
                $this->valueMatches($record->payment_method, $before['payment_method'] ?? null)
                    ? $changes['payment_method'] = $after['payment_method']
                    : $skipped++;
            }

            // Repayment records are dated on the day they were paid, so only same-day records move
            if (in_array('date', $changed, true) && $this->valueMatches($record->date?->toDateString(), $before['date'] ?? null)) {
                $changes['date'] = $after['date'];
            }

            // Only a record that stood for the whole credit (no payment of its own) follows a new total.
            // Repayment records keep the amount that was actually paid.
            if (in_array('amount', $changed, true)) {
                $coveredWholeAmount = abs($this->recordAmount($record) - (float) $before['amount']) < 0.01;
                if ($coveredWholeAmount && $record->creditDebtPayments()->doesntExist()) {
                    $changes['amount'] = $record instanceof Expense
                        ? round((float) $after['amount'] - (float) $record->gst_amount, 2)
                        : (float) $after['amount'];
                } else {
                    $skipped++;
                }
            }

            if ($changes !== []) {
                $record->update($changes);
                $updated++;
            }
        }

        // The credit's own payments follow a payment-method / date change the same way
        foreach ($creditDebt->payments as $payment) {
            $paymentChanges = [];
            if (in_array('payment_method', $changed, true) && $this->valueMatches($payment->payment_method, $before['payment_method'] ?? null)) {
                $paymentChanges['payment_method'] = $after['payment_method'];
            }
            if (in_array('date', $changed, true) && $this->valueMatches($payment->paid_on?->toDateString(), $before['date'] ?? null)) {
                $paymentChanges['paid_on'] = $after['date'];
            }
            if ($paymentChanges !== []) {
                $payment->update($paymentChanges);
            }
        }

        if ($updated > 0) {
            $summary[] = "Updated {$updated} linked record(s).";
        }
        if ($skipped > 0) {
            $summary[] = "Left {$skipped} linked value(s) unchanged because they were recorded with a different value (e.g. a partial repayment).";
        }

        return $summary;
    }

    /**
     * Apply edits made on a linked expense / personal expense / income back to its credit/debt.
     *
     * @param  array{payment_method?: string|null, date?: string|null, amount?: float|string|null}  $before
     * @param  array<int, string>  $fieldsToSync
     * @return array<int, string> human readable summary
     */
    public function syncCreditFromRecord(Expense|PersonalExpense|Income $record, array $before, array $fieldsToSync): array
    {
        $creditDebt = $record->creditDebt;
        if (! $creditDebt) {
            return [];
        }

        $after = [
            'payment_method' => $record->payment_method,
            'date' => $record->date?->toDateString(),
            'amount' => $this->recordAmount($record),
        ];
        $changed = $this->changedFields($before, $after, $fieldsToSync);
        if ($changed === []) {
            return [];
        }

        $summary = [];
        /** @var Collection<int, CreditDebtPayment> $payments */
        $payments = $record->creditDebtPayments()->get();

        // The payment(s) filed as this record are the same money movement
        foreach ($payments as $payment) {
            $paymentChanges = [];
            if (in_array('payment_method', $changed, true)) {
                $paymentChanges['payment_method'] = $after['payment_method'];
            }
            if (in_array('date', $changed, true)) {
                $paymentChanges['paid_on'] = $after['date'];
            }
            if ($paymentChanges !== []) {
                $payment->update($paymentChanges);
            }
        }

        if (in_array('payment_method', $changed, true) && $this->valueMatches($creditDebt->payment_method, $before['payment_method'] ?? null)) {
            $creditDebt->payment_method = $after['payment_method'];
        }

        if (in_array('amount', $changed, true)) {
            if ($payments->count() === 1) {
                $payment = $payments->first();
                $otherPayments = (float) $creditDebt->payments()->whereKeyNot($payment->id)->sum('amount');
                if ($otherPayments + $after['amount'] - (float) $creditDebt->amount > 0.01) {
                    $summary[] = 'Credit/debt payment not changed: ₹'.number_format($after['amount'], 2).' would exceed the total owed.';
                } else {
                    $payment->update(['amount' => $after['amount']]);
                }
            } elseif ($payments->isEmpty() && abs((float) $creditDebt->amount - (float) $before['amount']) < 0.01) {
                // The record stood for the whole credit (e.g. "link as expense" when it was created)
                $creditDebt->amount = $after['amount'];
            } else {
                $summary[] = 'Credit/debt amount not changed: this record covered only part of it.';
            }
        }

        $creditDebt->save();
        $creditDebt->refreshPaidAmount();
        $this->financeLinkService->syncCreditDebtToFriend($creditDebt->fresh());

        array_unshift($summary, ucfirst($creditDebt->type).' #'.$creditDebt->id.' updated to match.');

        return $summary;
    }

    /**
     * Read the "sync_linked[]" checkboxes posted by the prompt.
     *
     * @return array<int, string>
     */
    public function requestedFields(mixed $input): array
    {
        return array_values(array_intersect(self::SYNCABLE_FIELDS, (array) $input));
    }

    /**
     * @return Collection<int, Expense|PersonalExpense|Income>
     */
    private function linkedRecords(CreditDebt $creditDebt): Collection
    {
        return collect()
            ->concat($creditDebt->linkedExpenses()->get())
            ->concat($creditDebt->linkedPersonalExpenses()->get())
            ->concat($creditDebt->linkedIncomes()->get());
    }

    /**
     * @return array{payment_method: string|null, date: string|null, amount: float}
     */
    private function creditValues(CreditDebt $creditDebt): array
    {
        return [
            'payment_method' => $creditDebt->payment_method,
            'date' => $creditDebt->date ? Carbon::parse($creditDebt->date)->toDateString() : null,
            'amount' => (float) $creditDebt->amount,
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<int, string>  $fieldsToSync
     * @return array<int, string>
     */
    private function changedFields(array $before, array $after, array $fieldsToSync): array
    {
        return array_values(array_filter($fieldsToSync, function (string $field) use ($before, $after): bool {
            if (! array_key_exists($field, $before)) {
                return false;
            }

            return $field === 'amount'
                ? abs((float) $before[$field] - (float) $after[$field]) >= 0.01
                : (string) $before[$field] !== (string) $after[$field];
        }));
    }

    /**
     * A linked value "matches" when it still equals the old credit value
     * (or the credit had no value), so deliberate differences are preserved.
     */
    private function valueMatches(?string $recordValue, ?string $oldValue): bool
    {
        return blank($oldValue) || (string) $recordValue === (string) $oldValue;
    }

    private function recordAmount(Expense|PersonalExpense|Income $record): float
    {
        return $record instanceof Expense ? $record->totalAmount() : round((float) $record->amount, 2);
    }

    private function recordLabel(Expense|PersonalExpense|Income $record): string
    {
        $kind = match (true) {
            $record instanceof Expense => 'Normal Expense',
            $record instanceof PersonalExpense => 'Personal Expense',
            default => 'Income',
        };

        return $kind.' #'.$record->id.' · ₹'.number_format($this->recordAmount($record), 2)
            .' · '.($record->payment_method ?: 'No method').' · '.$record->date?->format('d M Y');
    }

    private function recordUrl(Expense|PersonalExpense|Income $record): string
    {
        return match (true) {
            $record instanceof Expense => route('expenses.show', $record),
            $record instanceof PersonalExpense => route('personal-expenses.index'),
            default => route('income.show', $record),
        };
    }
}
