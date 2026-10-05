<?php

namespace App\Http\Controllers;

use App\Models\DailyBalance;
use App\Models\Expense;
use App\Models\Income;
use App\Models\PaymentWallet;
use App\Models\Setting;
use App\Services\OptionsService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DailyBalanceController extends Controller
{
    public function __construct(
        private readonly OptionsService $options,
        private readonly WalletService $wallets
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Available & Active Categories
        $allPaymentMethods = $this->options->for('payment_method', $user->id);
        $allMethodNames = $allPaymentMethods->pluck('name')->all();
        if (empty($allMethodNames)) {
            $allMethodNames = ['Cash', 'UPI', 'Card', 'Bank Transfer', 'Other'];
        }

        // Active tracked categories in Daily Cash Register (Default: Cash, UPI)
        $activeCategories = Setting::getVal('daily_register_categories', ['Cash', 'UPI']);
        if (! is_array($activeCategories) || empty($activeCategories)) {
            $activeCategories = ['Cash', 'UPI'];
        }
        // Filter to ensure only valid payment methods are included
        $activeCategories = array_values(array_intersect($activeCategories, $allMethodNames));
        if (empty($activeCategories)) {
            $activeCategories = ['Cash', 'UPI'];
        }

        // 2. Selected Date & Month Range
        $today = Carbon::today();
        $todayStr = $today->toDateString();

        $selectedDateStr = $request->get('date', $todayStr);
        try {
            $selectedDate = Carbon::parse($selectedDateStr);
        } catch (\Throwable) {
            $selectedDate = $today->copy();
            $selectedDateStr = $todayStr;
        }

        $month = $request->get('month', $selectedDate->format('Y-m'));
        $startOfMonth = Carbon::parse($month.'-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Limit the history table up to today if viewing current month, or end of month if past month
        $maxHistoryDate = $today->format('Y-m') === $month
            ? ($selectedDate->gt($today) ? $selectedDate->copy() : $today->copy())
            : $endOfMonth->copy();

        // 3. Fetch Incomes & Expenses grouped by date and payment method
        $incomesRaw = Income::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $maxHistoryDate->toDateString()])
            ->selectRaw('date, payment_method, SUM(amount) as total')
            ->groupBy('date', 'payment_method')
            ->get();

        $incomesByDateAndMethod = [];
        foreach ($incomesRaw as $row) {
            $d = Carbon::parse($row->date)->toDateString();
            $method = $row->payment_method ?: 'Cash';
            $incomesByDateAndMethod[$d][$method] = (float) $row->total;
        }

        $expensesRaw = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->where(fn ($q) => $q->whereNull('paid_by')->orWhere('paid_by', '!=', 'friend'))
            ->whereBetween('date', [$startOfMonth->toDateString(), $maxHistoryDate->toDateString()])
            ->selectRaw('date, payment_method, SUM(amount + gst_amount) as total')
            ->groupBy('date', 'payment_method')
            ->get();

        $expensesByDateAndMethod = [];
        foreach ($expensesRaw as $row) {
            $d = Carbon::parse($row->date)->toDateString();
            $method = $row->payment_method ?: 'Cash';
            $expensesByDateAndMethod[$d][$method] = (float) $row->total;
        }

        // 4. Saved Daily Balances for this month
        $savedBalances = DailyBalance::query()
            ->where('user_id', $user->id)
            ->whereBetween('record_date', [$startOfMonth->toDateString(), $maxHistoryDate->toDateString()])
            ->get()
            ->keyBy(fn ($b) => $b->record_date->toDateString());

        // 5. Initial baseline opening balance per category prior to this month
        $lastPriorBalance = DailyBalance::query()
            ->where('user_id', $user->id)
            ->where('record_date', '<', $startOfMonth->toDateString())
            ->orderByDesc('record_date')
            ->first();

        $wallets = PaymentWallet::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('payment_method');

        $runningCategoryBalances = [];
        foreach ($activeCategories as $cat) {
            if ($lastPriorBalance && isset($lastPriorBalance->categories_data[$cat]['closing'])) {
                $runningCategoryBalances[$cat] = (float) $lastPriorBalance->categories_data[$cat]['closing'];
            } elseif ($wallets->has($cat)) {
                $runningCategoryBalances[$cat] = (float) $wallets->get($cat)->opening_balance;
            } else {
                $runningCategoryBalances[$cat] = 0.0;
            }
        }

        // 6. Build Historical Days (Past days up to Today / selectedDate)
        $days = [];
        $cursor = $startOfMonth->copy();

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

            foreach ($activeCategories as $cat) {
                $inflow = (float) ($incomesByDateAndMethod[$dStr][$cat] ?? 0.0);
                $outflow = (float) ($expensesByDateAndMethod[$dStr][$cat] ?? 0.0);

                $catSaved = $catData[$cat] ?? null;
                $isManualOpening = ! empty($catSaved['is_opening_manual']);
                $isManualClosing = ! empty($catSaved['is_closing_manual']);

                $opening = $isManualOpening
                    ? (float) $catSaved['opening']
                    : (float) ($runningCategoryBalances[$cat] ?? 0.0);

                $adjustment = (float) ($catSaved['adjustment'] ?? 0.0);

                $closing = $isManualClosing
                    ? (float) $catSaved['closing']
                    : ($opening + $inflow - $outflow + $adjustment);

                $dayCategories[$cat] = [
                    'opening' => round($opening, 2),
                    'inflow' => round($inflow, 2),
                    'outflow' => round($outflow, 2),
                    'adjustment' => round($adjustment, 2),
                    'closing' => round($closing, 2),
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

            // Fallback for legacy records that didn't have categories_data
            if ($saved && empty($catData) && $saved->closing_balance > 0) {
                $totalOpening = (float) $saved->opening_balance;
                $totalAdjustment = (float) $saved->manual_adjustment;
                $totalClosing = (float) $saved->closing_balance;
            }

            $days[$dStr] = [
                'date' => $cursor->copy(),
                'date_str' => $dStr,
                'is_today' => $cursor->isToday(),
                'is_selected' => $dStr === $selectedDateStr,
                'categories' => $dayCategories,
                'total' => [
                    'opening' => round($totalOpening, 2),
                    'inflow' => round($totalInflow, 2),
                    'outflow' => round($totalOutflow, 2),
                    'adjustment' => round($totalAdjustment, 2),
                    'closing' => round($totalClosing, 2),
                ],
                'is_opening_manual' => $saved?->is_opening_manual ?? false,
                'is_closing_manual' => $saved?->is_closing_manual ?? false,
                'notes' => $saved?->notes,
                'saved_id' => $saved?->id,
            ];

            $cursor->addDay();
        }

        // Selected Day Data
        $selectedDayData = $days[$selectedDateStr] ?? null;

        // Tomorrow's Rollover Preview (derived from today's closing, showing expected next day opening)
        $todayData = $days[$todayStr] ?? null;
        $tomorrowDateStr = $today->copy()->addDay()->toDateString();
        $tomorrowPreview = null;
        if ($todayData) {
            $tomorrowCategories = [];
            foreach ($activeCategories as $cat) {
                $tomorrowCategories[$cat] = $todayData['categories'][$cat]['closing'] ?? 0.0;
            }
            $tomorrowPreview = [
                'date' => $today->copy()->addDay(),
                'date_str' => $tomorrowDateStr,
                'categories' => $tomorrowCategories,
                'total' => $todayData['total']['closing'] ?? 0.0,
            ];
        }

        // 7. Detailed Transactions on Selected Date
        $selectedDayIncomes = Income::query()
            ->where('user_id', $user->id)
            ->where('date', $selectedDateStr)
            ->orderBy('id', 'desc')
            ->get();

        $selectedDayExpenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('date', $selectedDateStr)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->orderBy('time', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Prev and Next dates for navigation
        $prevDate = $selectedDate->copy()->subDay()->toDateString();
        $nextDate = $selectedDate->copy()->addDay()->toDateString();

        return view('finance.daily_balances.index', compact(
            'days',
            'month',
            'todayStr',
            'selectedDate',
            'selectedDateStr',
            'selectedDayData',
            'todayData',
            'tomorrowPreview',
            'activeCategories',
            'allPaymentMethods',
            'selectedDayIncomes',
            'selectedDayExpenses',
            'prevDate',
            'nextDate'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'record_date' => ['nullable', 'date'],
            'date' => ['nullable', 'date'],
            'categories' => ['nullable', 'array'],
            'opening_balance' => ['nullable', 'numeric'],
            'manual_adjustment' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'is_opening_manual' => ['nullable', 'boolean'],
            'is_closing_manual' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $recordDate = $validated['record_date'] ?? $validated['date'] ?? Carbon::today()->toDateString();

        $record = DailyBalance::query()->firstOrNew([
            'user_id' => $user->id,
            'record_date' => $recordDate,
        ]);

        $categoriesInput = $validated['categories'] ?? [];
        $existingCategoriesData = $record->categories_data ?? [];

        $totalOpening = 0.0;
        $totalAdjustment = 0.0;
        $totalClosing = 0.0;
        $hasCategoryUpdates = false;

        foreach ($categoriesInput as $catName => $fields) {
            $hasCategoryUpdates = true;
            $currentCat = $existingCategoriesData[$catName] ?? [];

            $opening = array_key_exists('opening', $fields) && $fields['opening'] !== null && $fields['opening'] !== ''
                ? (float) $fields['opening']
                : (float) ($currentCat['opening'] ?? 0.0);

            $adjustment = array_key_exists('adjustment', $fields) && $fields['adjustment'] !== null && $fields['adjustment'] !== ''
                ? (float) $fields['adjustment']
                : (float) ($currentCat['adjustment'] ?? 0.0);

            $closing = array_key_exists('closing', $fields) && $fields['closing'] !== null && $fields['closing'] !== ''
                ? (float) $fields['closing']
                : (float) ($currentCat['closing'] ?? 0.0);

            $isOpeningManual = ! empty($fields['is_opening_manual']) || (array_key_exists('opening', $fields) && $fields['opening'] !== null && $fields['opening'] !== '');
            $isClosingManual = ! empty($fields['is_closing_manual']) || (array_key_exists('closing', $fields) && $fields['closing'] !== null && $fields['closing'] !== '');

            $existingCategoriesData[$catName] = [
                'opening' => round($opening, 2),
                'adjustment' => round($adjustment, 2),
                'closing' => round($closing, 2),
                'is_opening_manual' => $isOpeningManual,
                'is_closing_manual' => $isClosingManual,
            ];

            $totalOpening += $opening;
            $totalAdjustment += $adjustment;
            $totalClosing += $closing;
        }

        if ($hasCategoryUpdates) {
            $record->categories_data = $existingCategoriesData;
            $record->opening_balance = $totalOpening;
            $record->manual_adjustment = $totalAdjustment;
            $record->closing_balance = $totalClosing;
            $record->is_opening_manual = true;
            $record->is_closing_manual = true;
        } else {
            // Direct overall adjustment (legacy or single input)
            if (array_key_exists('opening_balance', $validated) && $validated['opening_balance'] !== null) {
                $record->opening_balance = (float) $validated['opening_balance'];
                $record->is_opening_manual = true;
            }

            if (array_key_exists('manual_adjustment', $validated) && $validated['manual_adjustment'] !== null) {
                $record->manual_adjustment = (float) $validated['manual_adjustment'];
            }

            if (array_key_exists('closing_balance', $validated) && $validated['closing_balance'] !== null) {
                $record->closing_balance = (float) $validated['closing_balance'];
                $record->is_closing_manual = true;
            }
        }

        if (array_key_exists('notes', $validated)) {
            $record->notes = $validated['notes'];
        }

        $record->save();

        return redirect()->route('daily-balances.index', ['date' => $recordDate])
            ->with('success', 'Daily balance register updated for '.Carbon::parse($recordDate)->format('d M Y').'.');
    }

    public function updateCategories(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string'],
        ]);

        Setting::setVal('daily_register_categories', $validated['categories'], 'json', 'finance', 'Active payment categories in Daily Cash Register');

        return back()->with('success', 'Daily cash register payment categories updated.');
    }
}
