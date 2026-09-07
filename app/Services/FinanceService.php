<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Friend;
use App\Models\Income;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Models\Setting;
use App\Models\Settlement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    public function canEdit(string $module, object $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        if (property_exists($record, 'is_locked') && $record->is_locked) {
            return false;
        }

        $key = match ($module) {
            'expense' => 'expense_edit_window_days',
            'income' => 'income_edit_window_days',
            'payment' => 'payment_edit_window_days',
            'petrol' => 'petrol_edit_window_days',
            default => 'expense_edit_window_days',
        };

        $windowDays = (int) Setting::getVal($key, 7);
        if ($windowDays === 0) {
            return false;
        }

        return Carbon::parse($record->created_at)->addDays($windowDays)->isFuture();
    }

    public function reconcilePayment(Payment $payment, array $data): PaymentReconciliation
    {
        return DB::transaction(function () use ($payment, $data) {
            $oldStatus = $payment->status;

            $reconciliation = PaymentReconciliation::create([
                'payment_id' => $payment->id,
                'user_id' => auth()->id(),
                'reconciled_date' => $data['reconciled_date'] ?? Carbon::today()->toDateString(),
                'reconciled_amount' => $data['reconciled_amount'] ?? $payment->amount,
                'reconciled_by' => auth()->user()->name,
                'notes' => $data['notes'] ?? null,
            ]);

            $payment->update([
                'status' => 'Reconciled',
                'is_locked' => true,
            ]);

            AuditService::log(
                module: 'payment',
                recordId: $payment->id,
                action: 'reconciled',
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'Reconciled', 'reconciled_amount' => $reconciliation->reconciled_amount],
                reason: $data['notes'] ?? 'Payment reconciled successfully'
            );

            return $reconciliation;
        });
    }

    public function settleWithFriend(
        Friend $friend,
        float $amount,
        string $direction,
        string $paymentMethod,
        ?string $notes = null
    ): Settlement {
        return DB::transaction(function () use ($friend, $amount, $direction, $paymentMethod, $notes) {
            $settlement = Settlement::create([
                'user_id' => auth()->id(),
                'friend_id' => $friend->id,
                'amount' => $amount,
                'direction' => $direction,
                'date' => Carbon::today()->toDateString(),
                'payment_method' => $paymentMethod,
                'notes' => $notes,
            ]);

            AuditService::log(
                module: 'settlement',
                recordId: $settlement->id,
                action: 'created',
                newValues: ['amount' => $amount, 'direction' => $direction, 'friend' => $friend->name],
                reason: 'Friend balance settlement'
            );

            return $settlement;
        });
    }

    /**
     * @return array{total_income: float, total_expenses: float, voluntary_spend: float, net_savings: float, pending_payments: float}
     */
    public function getMonthlyStats(int $userId, ?int $year = null, ?int $month = null): array
    {
        $year = $year ?? Carbon::today()->year;
        $month = $month ?? Carbon::today()->month;

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $totalExpenses = (float) $this->expenseBaseQuery($userId, $start->toDateString(), $end->toDateString())
            ->sum(DB::raw('expenses.amount + expenses.gst_amount'));

        $totalIncome = (float) Income::query()
            ->where('incomes.user_id', $userId)
            ->whereBetween('incomes.date', [$start->toDateString(), $end->toDateString()])
            ->leftJoin('income_categories', 'incomes.category_id', '=', 'income_categories.id')
            ->where(function ($query) {
                $query->whereNull('income_categories.is_archived')
                    ->orWhere('income_categories.is_archived', false);
            })
            ->sum('incomes.amount');

        $voluntarySpend = (float) Expense::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('is_voluntary', true)
            ->sum(DB::raw('amount + gst_amount'));

        $pendingPayments = (float) Payment::query()
            ->where('user_id', $userId)
            ->where('status', 'Pending')
            ->sum('amount');

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'voluntary_spend' => $voluntarySpend,
            'net_savings' => $totalIncome - $totalExpenses,
            'pending_payments' => $pendingPayments,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function monthlyExpenseByCategory(int $userId, ?int $year = null, ?int $month = null): Collection
    {
        [$from, $to] = $this->monthBounds($year, $month);

        return $this->expenseBaseQuery($userId, $from, $to)
            ->selectRaw('expense_categories.name, expense_categories.color, sum(expenses.amount + expenses.gst_amount) as total')
            ->groupBy('expense_categories.name', 'expense_categories.color')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function monthlyExpenseByPaymentMethod(int $userId, ?int $year = null, ?int $month = null): Collection
    {
        [$from, $to] = $this->monthBounds($year, $month);

        return $this->expenseBaseQuery($userId, $from, $to)
            ->selectRaw('expenses.payment_method, sum(expenses.amount + expenses.gst_amount) as total')
            ->groupBy('expenses.payment_method')
            ->orderByDesc('total')
            ->get();
    }

    public function expenseBaseQuery(int $userId, ?string $from = null, ?string $to = null): Builder
    {
        $query = Expense::query()
            ->where('expenses.user_id', $userId)
            ->whereNull('expenses.parent_id')
            ->where('expenses.is_voluntary', false)
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->where(function ($builder) {
                $builder->whereNull('expense_categories.is_archived')
                    ->orWhere('expense_categories.is_archived', false);
            });

        if ($from && $to) {
            $query->whereBetween('expenses.date', [$from, $to]);
        }

        return $query;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function monthBounds(?int $year = null, ?int $month = null): array
    {
        $date = Carbon::createFromDate($year ?? now()->year, $month ?? now()->month, 1);

        return [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()];
    }
}
