<?php

namespace App\Http\Controllers;

use App\Models\DailyBalance;
use App\Models\Expense;
use App\Models\Income;
use App\Models\PaymentWallet;
use App\Models\Setting;
use App\Services\DailyRegisterService;
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
        private readonly WalletService $wallets,
        private readonly DailyRegisterService $registerService
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

        $activeCategories = $this->registerService->getActiveCategories($user->id);

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

        // 3. Compute Full Daily Register Ledger via DailyRegisterService
        $calc = $this->registerService->computeDailyRegisterForMonth(
            $user,
            $startOfMonth,
            $maxHistoryDate,
            $activeCategories,
            $selectedDate
        );

        $days = $calc['days'];
        $selectedDayData = $calc['selectedDayData'];
        $totMonthInflow = $calc['totMonthInflow'];
        $totMonthOutflow = $calc['totMonthOutflow'];
        $totMonthAdjustment = $calc['totMonthAdjustment'];
        $runningCategoryBalances = $calc['runningBalances'];

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

    /**
     * Dedicated live breakdown page for Today's Cash & UPI Register flow.
     */
    public function today(Request $request): View
    {
        $user = $request->user();

        // 1. Available & Active tracked Categories
        $allPaymentMethods = $this->options->for('payment_method', $user->id);
        $allMethodNames = $allPaymentMethods->pluck('name')->all();
        if (empty($allMethodNames)) {
            $allMethodNames = ['Cash', 'UPI', 'Card', 'Bank Transfer', 'Other'];
        }

        $activeCategories = Setting::getVal('daily_register_categories', ['Cash', 'UPI']);
        if (! is_array($activeCategories) || empty($activeCategories)) {
            $activeCategories = ['Cash', 'UPI'];
        }
        $activeCategories = array_values(array_intersect($activeCategories, $allMethodNames));
        if (empty($activeCategories)) {
            $activeCategories = ['Cash', 'UPI'];
        }

        $today = Carbon::today();
        $todayStr = $today->toDateString();

        // Incomes for today
        $todayIncomes = Income::query()
            ->where('user_id', $user->id)
            ->where('date', $todayStr)
            ->with('category')
            ->orderByDesc('id')
            ->get();

        // Expenses for today
        $todayExpenses = Expense::query()
            ->where('user_id', $user->id)
            ->where('date', $todayStr)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->where(fn ($q) => $q->whereNull('paid_by')->orWhere('paid_by', '!=', 'friend'))
            ->with(['category', 'friendSplits.friend'])
            ->orderByDesc('id')
            ->get();

        // Saved today record
        $savedToday = DailyBalance::query()
            ->where('user_id', $user->id)
            ->where('record_date', $todayStr)
            ->first();

        // Baseline opening balance from yesterday or most recent prior record
        $lastPriorBalance = DailyBalance::query()
            ->where('user_id', $user->id)
            ->where('record_date', '<', $todayStr)
            ->orderByDesc('record_date')
            ->first();

        $wallets = PaymentWallet::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('payment_method');

        $categoriesBreakdown = [];
        $totalOpening = 0.0;
        $totalInflow = 0.0;
        $totalOutflow = 0.0;
        $totalNetFlow = 0.0;
        $totalClosing = 0.0;

        foreach ($activeCategories as $cat) {
            $catIncomes = $todayIncomes->filter(fn ($i) => ($i->payment_method ?: 'Cash') === $cat);
            $catExpenses = $todayExpenses->filter(fn ($e) => ($e->payment_method ?: 'Cash') === $cat);

            $inflow = (float) $catIncomes->sum('amount');
            $outflow = (float) $catExpenses->sum(fn ($e) => (float) $e->amount + (float) ($e->gst_amount ?? 0));

            // Determine opening
            $savedOpening = $savedToday?->categories_data[$cat]['opening'] ?? null;
            if ($savedOpening !== null) {
                $opening = (float) $savedOpening;
            } elseif ($lastPriorBalance && isset($lastPriorBalance->categories_data[$cat]['closing'])) {
                $opening = (float) $lastPriorBalance->categories_data[$cat]['closing'];
            } elseif ($wallets->has($cat)) {
                $opening = (float) $wallets->get($cat)->opening_balance;
            } else {
                $opening = 0.0;
            }

            $adjustment = (float) ($savedToday?->categories_data[$cat]['adjustment'] ?? 0.0);
            $adjNote = $savedToday?->categories_data[$cat]['adjustment_note'] ?? null;
            $netFlow = round($inflow - $outflow, 2);
            $closing = round($opening + $inflow - $outflow + $adjustment, 2);

            $categoriesBreakdown[$cat] = [
                'name' => $cat,
                'opening' => round($opening, 2),
                'inflow' => round($inflow, 2),
                'outflow' => round($outflow, 2),
                'net_flow' => $netFlow,
                'adjustment' => round($adjustment, 2),
                'adjustment_note' => $adjNote,
                'closing' => $closing,
                'incomes' => $catIncomes,
                'expenses' => $catExpenses,
            ];

            $totalOpening += $opening;
            $totalInflow += $inflow;
            $totalOutflow += $outflow;
            $totalNetFlow += $netFlow;
            $totalClosing += $closing;
        }

        $totals = [
            'opening' => round($totalOpening, 2),
            'inflow' => round($totalInflow, 2),
            'outflow' => round($totalOutflow, 2),
            'net_flow' => round($totalNetFlow, 2),
            'closing' => round($totalClosing, 2),
        ];

        return view('finance.daily_balances.today', compact(
            'today',
            'categoriesBreakdown',
            'totals',
            'activeCategories',
            'todayIncomes',
            'todayExpenses',
            'savedToday'
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

            $adjNote = array_key_exists('adjustment_note', $fields) && filled($fields['adjustment_note'])
                ? trim((string) $fields['adjustment_note'])
                : null;

            $isOpeningManual = ! empty($fields['is_opening_manual']) || (array_key_exists('opening', $fields) && $fields['opening'] !== null && $fields['opening'] !== '');
            $isClosingManual = ! empty($fields['is_closing_manual']) || (array_key_exists('closing', $fields) && $fields['closing'] !== null && $fields['closing'] !== '');

            $existingCategoriesData[$catName] = [
                'opening' => round($opening, 2),
                'adjustment' => round($adjustment, 2),
                'adjustment_note' => $adjNote,
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
