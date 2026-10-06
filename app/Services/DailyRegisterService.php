<?php

namespace App\Services;

use App\Models\DailyBalance;
use App\Models\Expense;
use App\Models\Income;
use App\Models\PaymentWallet;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class DailyRegisterService
{
    public function __construct(
        private readonly OptionsService $options
    ) {}

    /**
     * Get active tracked payment categories for daily cash register.
     *
     * @return array<string>
     */
    public function getActiveCategories(int $userId): array
    {
        $allPaymentMethods = $this->options->for('payment_method', $userId);
        $allMethodNames = $allPaymentMethods->pluck('name')->all();
        if (empty($allMethodNames)) {
            $allMethodNames = ['Cash', 'UPI', 'Card', 'Bank Transfer', 'Other'];
        }

        $activeCategories = Setting::getVal('daily_register_categories', ['Cash', 'UPI']);
        if (! is_array($activeCategories) || empty($activeCategories)) {
            $activeCategories = ['Cash', 'UPI'];
        }

        $activeCategories = array_values(array_intersect($activeCategories, $allMethodNames));

        return empty($activeCategories) ? ['Cash', 'UPI'] : $activeCategories;
    }

    /**
     * Compute the full daily cash register sequence for a month.
     *
     * @param  array<string>  $activeCategories
     * @return array{
     *     days: array<string, array<string, mixed>>,
     *     selectedDayData: ?array<string, mixed>,
     *     totMonthInflow: float,
     *     totMonthOutflow: float,
     *     totMonthAdjustment: float,
     *     runningBalances: array<string, float>
     * }
     */
    public function computeDailyRegisterForMonth(
        User $user,
        Carbon $startOfMonth,
        Carbon $maxHistoryDate,
        array $activeCategories,
        Carbon $selectedDate
    ): array {
        $todayStr = Carbon::today()->toDateString();
        $selectedDateStr = $selectedDate->toDateString();

        // 1. Saved Daily Balances for this month
        $savedBalances = DailyBalance::query()
            ->where('user_id', $user->id)
            ->whereBetween('record_date', [$startOfMonth->toDateString(), $maxHistoryDate->toDateString()])
            ->get()
            ->keyBy(fn ($b) => $b->record_date->toDateString());

        // 2. Initial baseline opening balance prior to this month
        $lastPriorBalance = DailyBalance::query()
            ->where('user_id', $user->id)
            ->where('record_date', '<', $startOfMonth->toDateString())
            ->orderByDesc('record_date')
            ->first();

        $wallets = PaymentWallet::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('payment_method');

        // Setup running balances at start of month
        $runningCategoryBalances = [];
        foreach ($activeCategories as $cat) {
            if ($lastPriorBalance && isset($lastPriorBalance->categories_data[$cat]['closing'])) {
                $runningCategoryBalances[$cat] = (float) $lastPriorBalance->categories_data[$cat]['closing'];
            } elseif ($wallets->has($cat)) {
                $wallet = $wallets->get($cat);
                $asOf = $wallet->opening_as_of ? Carbon::parse($wallet->opening_as_of)->toDateString() : ($wallet->created_at ? $wallet->created_at->toDateString() : null);

                // If wallet was established AFTER the start of this month, do NOT apply it on day 1
                if ($asOf && $asOf > $startOfMonth->toDateString()) {
                    $runningCategoryBalances[$cat] = 0.0;
                } else {
                    $runningCategoryBalances[$cat] = (float) $wallet->opening_balance;
                }
            } else {
                $runningCategoryBalances[$cat] = 0.0;
            }
        }

        // 3. Fetch Incomes & Expenses for the month
        $monthIncomes = Income::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $maxHistoryDate->toDateString()])
            ->with('category')
            ->orderBy('time', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $incomesByDateAndMethod = [];
        $incomeDetailsByDateAndMethod = [];
        foreach ($monthIncomes as $inc) {
            $d = Carbon::parse($inc->date)->toDateString();
            $method = $inc->payment_method ?: 'Cash';
            $incomesByDateAndMethod[$d][$method] = ($incomesByDateAndMethod[$d][$method] ?? 0.0) + (float) $inc->amount;
            $incomeDetailsByDateAndMethod[$d][$method][] = [
                'id' => $inc->id,
                'source' => $inc->source ?: 'Income',
                'amount' => (float) $inc->amount,
                'category' => $inc->category?->name ?? 'General',
                'time' => $inc->time ? substr($inc->time, 0, 5) : null,
                'notes' => $inc->notes,
            ];
        }

        $monthExpenses = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->where(fn ($q) => $q->whereNull('paid_by')->orWhere('paid_by', '!=', 'friend'))
            ->whereBetween('date', [$startOfMonth->toDateString(), $maxHistoryDate->toDateString()])
            ->with('category')
            ->orderBy('time', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $expensesByDateAndMethod = [];
        $expenseDetailsByDateAndMethod = [];
        foreach ($monthExpenses as $exp) {
            $d = Carbon::parse($exp->date)->toDateString();
            $method = $exp->payment_method ?: 'Cash';
            $tot = (float) $exp->amount + (float) ($exp->gst_amount ?? 0);
            $expensesByDateAndMethod[$d][$method] = ($expensesByDateAndMethod[$d][$method] ?? 0.0) + $tot;
            $expenseDetailsByDateAndMethod[$d][$method][] = [
                'id' => $exp->id,
                'description' => $exp->description ?: 'Expense',
                'amount' => $tot,
                'category' => $exp->category?->name ?? 'General',
                'time' => $exp->time ? substr($exp->time, 0, 5) : null,
                'notes' => $exp->notes,
            ];
        }

        // 4. Build sequence day by day
        $days = [];
        $cursor = $startOfMonth->copy();
        $selectedDayData = null;

        $totMonthInflow = 0.0;
        $totMonthOutflow = 0.0;
        $totMonthAdjustment = 0.0;

        while ($cursor->lte($maxHistoryDate)) {
            $dStr = $cursor->toDateString();
            $saved = $savedBalances->get($dStr);
            $catData = $saved?->categories_data ?? [];

            $dayCategories = [];
            $totalOpening = 0.0;
            $totalInflow = 0.0;
            $totalOutflow = 0.0;
            $totalAdjustment = 0.0;
            $totalClosing = 0.0;
            $dayAllIncomes = [];
            $dayAllExpenses = [];

            foreach ($activeCategories as $cat) {
                $inflow = (float) ($incomesByDateAndMethod[$dStr][$cat] ?? 0.0);
                $outflow = (float) ($expensesByDateAndMethod[$dStr][$cat] ?? 0.0);

                $catIncomes = $incomeDetailsByDateAndMethod[$dStr][$cat] ?? [];
                $catExpenses = $expenseDetailsByDateAndMethod[$dStr][$cat] ?? [];

                $dayAllIncomes = array_merge($dayAllIncomes, $catIncomes);
                $dayAllExpenses = array_merge($dayAllExpenses, $catExpenses);

                $catSaved = $catData[$cat] ?? null;
                $isManualOpening = ! empty($catSaved['is_opening_manual']);
                $isManualClosing = ! empty($catSaved['is_closing_manual']);

                // If this day reaches the wallet's opening_as_of date, and there was no prior month closing balance
                $wallet = $wallets->get($cat);
                $asOf = $wallet?->opening_as_of ? Carbon::parse($wallet->opening_as_of)->toDateString() : ($wallet?->created_at ? $wallet->created_at->toDateString() : null);

                if (! $isManualOpening && $asOf && $dStr === $asOf && ! ($lastPriorBalance && isset($lastPriorBalance->categories_data[$cat]['closing']))) {
                    $runningCategoryBalances[$cat] = (float) $wallet->opening_balance;
                }

                $opening = $isManualOpening
                    ? (float) $catSaved['opening']
                    : (float) ($runningCategoryBalances[$cat] ?? 0.0);

                $adjustment = (float) ($catSaved['adjustment'] ?? 0.0);

                // Closing is mathematically Opening + Inflow - Outflow + Adjustment.
                // Dynamic inflows and outflows must ALWAYS flow into closing (especially for today and whenever transactions exist).
                $closing = ($dStr === $todayStr || ($inflow > 0 || $outflow > 0) || ! $isManualClosing)
                    ? ($opening + $inflow - $outflow + $adjustment)
                    : (float) $catSaved['closing'];

                $dayCategories[$cat] = [
                    'name' => $cat,
                    'opening' => round($opening, 2),
                    'inflow' => round($inflow, 2),
                    'outflow' => round($outflow, 2),
                    'adjustment' => round($adjustment, 2),
                    'adjustment_note' => $catSaved['adjustment_note'] ?? null,
                    'closing' => round($closing, 2),
                    'net_flow' => round($inflow - $outflow, 2),
                    'income_items' => $catIncomes,
                    'expense_items' => $catExpenses,
                    'is_opening_manual' => $isManualOpening,
                    'is_closing_manual' => $isManualClosing,
                ];

                $totalOpening += $opening;
                $totalInflow += $inflow;
                $totalOutflow += $outflow;
                $totalAdjustment += $adjustment;
                $totalClosing += $closing;

                $runningCategoryBalances[$cat] = $closing;
            }

            // Fallback for legacy records
            if ($saved && empty($catData) && $saved->closing_balance > 0) {
                $totalOpening = (float) $saved->opening_balance;
                $totalAdjustment = (float) $saved->manual_adjustment;
                $totalClosing = (float) $saved->closing_balance;
            }

            $dayEntry = [
                'date' => $cursor->copy(),
                'date_str' => $dStr,
                'is_today' => ($dStr === $todayStr),
                'is_selected' => ($dStr === $selectedDateStr),
                'categories' => $dayCategories,
                'total' => [
                    'opening' => round($totalOpening, 2),
                    'inflow' => round($totalInflow, 2),
                    'outflow' => round($totalOutflow, 2),
                    'adjustment' => round($totalAdjustment, 2),
                    'closing' => round($totalClosing, 2),
                    'net_flow' => round($totalInflow - $totalOutflow, 2),
                    'income_items' => $dayAllIncomes,
                    'expense_items' => $dayAllExpenses,
                ],
                'notes' => $saved?->notes,
                'has_saved_record' => (bool) $saved,
            ];

            $days[$dStr] = $dayEntry;

            if ($dStr === $selectedDateStr) {
                $selectedDayData = $dayEntry;
            }

            $totMonthInflow += $totalInflow;
            $totMonthOutflow += $totalOutflow;
            $totMonthAdjustment += $totalAdjustment;

            $cursor->addDay();
        }

        return [
            'days' => $days,
            'selectedDayData' => $selectedDayData,
            'totMonthInflow' => round($totMonthInflow, 2),
            'totMonthOutflow' => round($totMonthOutflow, 2),
            'totMonthAdjustment' => round($totMonthAdjustment, 2),
            'runningBalances' => $runningCategoryBalances,
        ];
    }

    /**
     * Get the daily cash register figures for a specific date.
     *
     * @return array<string, mixed>
     */
    public function getRegisterForDate(User $user, Carbon|string $date): array
    {
        $d = is_string($date) ? Carbon::parse($date) : $date->copy();
        $startOfMonth = $d->copy()->startOfMonth();
        $activeCategories = $this->getActiveCategories($user->id);

        $result = $this->computeDailyRegisterForMonth($user, $startOfMonth, $d->copy(), $activeCategories, $d);

        return $result['selectedDayData'] ?? [
            'date' => $d,
            'date_str' => $d->toDateString(),
            'is_today' => $d->isToday(),
            'categories' => [],
            'total' => [
                'opening' => 0.0,
                'inflow' => 0.0,
                'outflow' => 0.0,
                'adjustment' => 0.0,
                'closing' => 0.0,
                'net_flow' => 0.0,
                'income_items' => [],
                'expense_items' => [],
            ],
            'notes' => null,
            'has_saved_record' => false,
        ];
    }
}
