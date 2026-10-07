<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\DailyBalance;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\FoodEntry;
use App\Models\FuelEntry;
use App\Models\Income;
use App\Models\IncomeExpenseTally;
use App\Models\Mistake;
use App\Models\PersonalExpense;
use App\Models\Saving;
use App\Models\ScooterTrip;
use App\Models\Setting;
use App\Services\DailyPromptService;
use App\Services\DailyRegisterService;
use App\Services\DayTimelineService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        DailyPromptService $promptService,
        DayTimelineService $timelineService,
        WalletService $walletService,
        DailyRegisterService $dailyRegisterService
    ) {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $date = $request->get('date', $today);
        if ($date > $today) {
            $date = $today;
        }

        $period = $request->get('period', 'today');
        if (! in_array($period, ['today', 'week', 'month', 'custom'], true)) {
            $period = 'today';
        }

        $selectedMonth = $request->get('month', Carbon::parse($date)->format('Y-m'));

        if ($period === 'month') {
            $startMonthCarbon = Carbon::parse($selectedMonth.'-01')->startOfMonth();
            $startDate = $startMonthCarbon->toDateString();
            $endDate = $startMonthCarbon->copy()->endOfMonth()->toDateString();
        } elseif ($period === 'custom') {
            $startDate = $request->get('start_date', Carbon::parse($date)->startOfMonth()->toDateString());
            $endDate = $request->get('end_date', $date);
        } elseif ($period === 'week') {
            $startDate = Carbon::parse($date)->startOfWeek()->toDateString();
            $endDate = Carbon::parse($date)->endOfWeek()->toDateString();
        } else {
            $startDate = $date;
            $endDate = $date;
        }

        $dayRecord = DailyRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('record_date', $date)
            ->first();

        // Viewing a day must not create a record for it; an unsaved one renders the same
        $dayRecord ??= new DailyRecord([
            'user_id' => $user->id,
            'record_date' => $date,
        ]);

        $yesterday = Carbon::parse($date)->subDay()->toDateString();
        $yesterdayRecord = DailyRecord::where('user_id', $user->id)
            ->where('record_date', $yesterday)
            ->first();

        $workings = $timelineService->dayWorkings($user->id, $date);

        $showPersonalInDashboard = (bool) Setting::getVal('show_personal_expenses_in_dashboard', false);

        if ($period === 'today') {
            $moneyReceived = $workings['income'];
            $expensesTotal = $workings['expenses'];
            if ($showPersonalInDashboard) {
                $expensesTotal += (float) PersonalExpense::where('user_id', $user->id)->where('date', $date)->whereNull('expense_id')->where('is_archived', false)->sum('amount');
            }
            $snacksCount = $workings['snack_count'];
            $petrolSpent = $workings['fuel'];
            $activitiesCount = Activity::where('user_id', $user->id)->where('date', $date)->count();
            $scooterTripsCount = ScooterTrip::where('user_id', $user->id)->where('date', $date)->count();
            $scooterDistanceKm = (float) ScooterTrip::where('user_id', $user->id)->where('date', $date)->sum('distance_km');
            $mistakesCount = Mistake::where('user_id', $user->id)->where('date', $date)->count();
        } else {
            $moneyReceived = (float) Income::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->sum('amount');
            $expensesTotal = (float) Expense::where('user_id', $user->id)->whereNull('parent_id')->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))->whereBetween('date', [$startDate, $endDate])->sum(DB::raw('amount + gst_amount'));
            if ($showPersonalInDashboard) {
                $expensesTotal += (float) PersonalExpense::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->whereNull('expense_id')->where('is_archived', false)->sum('amount');
            }
            $snacksCount = FoodEntry::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->where('is_snack', true)->count();
            $petrolSpent = (float) FuelEntry::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->sum('amount');
            $activitiesCount = Activity::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->count();
            $scooterTripsCount = ScooterTrip::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->count();
            $scooterDistanceKm = (float) ScooterTrip::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->sum('distance_km');
            $mistakesCount = Mistake::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->count();
        }

        $activePrompts = $promptService->getActivePrompts($user->id);
        $timeline = $timelineService->forDate($user->id, $date);

        // Wallets & Current Balances
        $wallets = $walletService->displayWallets($user->id);
        $currentBalance = $walletService->currentBalanceTotal($user->id);

        $initialChart = $this->buildSpendingChart($user->id, '7d', $request, $showPersonalInDashboard);
        $last7Days = $initialChart['labels'];
        $expenseChartData = $initialChart['data'];
        $expenseChartRanges = $initialChart['ranges'];

        $prevDate = Carbon::parse($date)->subDay()->toDateString();
        $nextDate = Carbon::parse($date)->addDay()->toDateString();
        if ($nextDate > $today) {
            $nextDate = $today;
        }
        $isToday = $date === $today;
        $todayRecord = $dayRecord;

        // Weekly and Monthly Totals (always available on dashboard)
        $startOfWeek = Carbon::parse($date)->startOfWeek()->toDateString();
        $endOfWeek = Carbon::parse($date)->endOfWeek()->toDateString();
        $startOfMonth = Carbon::parse($selectedMonth.'-01')->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($selectedMonth.'-01')->endOfMonth()->toDateString();

        $weeklyExpensesTotal = (float) Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->sum(DB::raw('amount + gst_amount'));

        $monthlyExpensesTotal = (float) Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum(DB::raw('amount + gst_amount'));

        $monthlyBaseExpenses = (float) Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where('is_voluntary', false)
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum(DB::raw('amount + gst_amount'));

        $monthlyVoluntaryExpenses = (float) Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where('is_voluntary', true)
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum(DB::raw('amount + gst_amount'));

        $monthlyCategoryBreakdown = Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->with('category')
            ->get()
            ->groupBy(fn ($e) => $e->category?->name ?? 'Uncategorized')
            ->map(function ($group, $name) use ($monthlyExpensesTotal) {
                $cTotal = round((float) $group->sum(fn ($e) => (float) $e->amount + (float) $e->gst_amount), 2);

                return [
                    'name' => $name,
                    'count' => $group->count(),
                    'total' => $cTotal,
                    'percentage' => $monthlyExpensesTotal > 0 ? round(($cTotal / $monthlyExpensesTotal) * 100, 1) : 0,
                    'color' => $group->first()->category?->color ?? '#64748b',
                ];
            })
            ->sortByDesc('total')
            ->values();

        $monthlyPaymentBreakdown = Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(fn ($e) => $e->payment_method ?: 'Other')
            ->map(function ($group, $method) use ($monthlyExpensesTotal) {
                $pTotal = round((float) $group->sum(fn ($e) => (float) $e->amount + (float) $e->gst_amount), 2);

                return [
                    'method' => $method,
                    'count' => $group->count(),
                    'total' => $pTotal,
                    'percentage' => $monthlyExpensesTotal > 0 ? round(($pTotal / $monthlyExpensesTotal) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $weeklyIncomeTotal = (float) Income::where('user_id', $user->id)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->sum('amount');

        $monthlyIncomeTotal = (float) Income::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $totalSavingsAvailable = (float) Saving::query()->where('user_id', $user->id)->get()->sum(fn ($s) => $s->netAvailable());
        $totalIncomeTallied = (float) IncomeExpenseTally::query()->where('user_id', $user->id)->sum('allocated_amount');
        $totalIncomeAll = (float) Income::query()->where('user_id', $user->id)->sum('amount');
        $totalIncomeSurplus = max(0, $totalIncomeAll - $totalIncomeTallied);

        $todayDailyBalance = DailyBalance::query()
            ->where('user_id', $user->id)
            ->where('record_date', $today)
            ->first();

        $dailyRegister = $dailyRegisterService->getRegisterForDate($user, $date);

        return view('dashboard.index', compact(
            'todayRecord',
            'yesterdayRecord',
            'moneyReceived',
            'expensesTotal',
            'weeklyExpensesTotal',
            'monthlyExpensesTotal',
            'monthlyBaseExpenses',
            'monthlyVoluntaryExpenses',
            'monthlyCategoryBreakdown',
            'monthlyPaymentBreakdown',
            'weeklyIncomeTotal',
            'monthlyIncomeTotal',
            'startOfWeek',
            'endOfWeek',
            'startOfMonth',
            'endOfMonth',
            'snacksCount',
            'activitiesCount',
            'scooterTripsCount',
            'scooterDistanceKm',
            'petrolSpent',
            'mistakesCount',
            'activePrompts',
            'timeline',
            'last7Days',
            'expenseChartData',
            'expenseChartRanges',
            'date',
            'prevDate',
            'nextDate',
            'isToday',
            'workings',
            'period',
            'selectedMonth',
            'startDate',
            'endDate',
            'wallets',
            'currentBalance',
            'totalSavingsAvailable',
            'totalIncomeTallied',
            'totalIncomeSurplus',
            'todayDailyBalance',
            'dailyRegister'
        ));
    }

    public function chartData(Request $request): JsonResponse
    {
        $range = $request->get('range', '7d');
        $includePersonal = (bool) Setting::getVal('show_personal_expenses_in_dashboard', false);
        $chart = $this->buildSpendingChart($request->user()->id, $range, $request, $includePersonal);
        $total = round(array_sum($chart['data']), 2);

        return response()->json([
            'range' => $range,
            'labels' => $chart['labels'],
            'data' => $chart['data'],
            'ranges' => $chart['ranges'],
            'total' => $total,
            'title' => $chart['title'],
            'sub' => $chart['sub'].' (Total: ₹'.number_format($total, 2).')',
        ]);
    }

    /**
     * Category breakdown (and items) for one clicked chart bar.
     */
    public function chartBreakdown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $userId = $request->user()->id;
        $includePersonal = (bool) Setting::getVal('show_personal_expenses_in_dashboard', false);

        $expenses = Expense::where('user_id', $userId)
            ->whereNull('parent_id')
            ->whereDate('date', '>=', $validated['start'])->whereDate('date', '<=', $validated['end'])
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Expense $expense) => [
                'category' => $expense->category?->name ?? 'Uncategorized',
                'color' => $expense->category?->color ?? '#94a3b8',
                'description' => $expense->description ?? 'Expense',
                'date' => $expense->date?->format('d M Y'),
                'amount' => round((float) $expense->amount + (float) ($expense->gst_amount ?? 0), 2),
                'url' => route('expenses.show', $expense),
            ]);

        if ($includePersonal) {
            $expenses = $expenses->concat(
                PersonalExpense::where('user_id', $userId)
                    ->whereDate('date', '>=', $validated['start'])->whereDate('date', '<=', $validated['end'])
                    ->where('is_archived', false)
                    ->with('category')
                    ->orderByDesc('date')
                    ->get()
                    ->map(fn (PersonalExpense $expense) => [
                        'category' => 'Personal · '.($expense->category?->name ?? 'Uncategorized'),
                        'color' => $expense->category?->color ?? '#ec4899',
                        'description' => $expense->description ?? 'Personal expense',
                        'date' => $expense->date?->format('d M Y'),
                        'amount' => round((float) $expense->amount, 2),
                        'url' => route('personal-expenses.index'),
                    ])
            );
        }

        $total = round($expenses->sum('amount'), 2);
        $categories = $expenses->groupBy('category')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'color' => $items->first()['color'],
                'count' => $items->count(),
                'total' => round($items->sum('amount'), 2),
                'percentage' => $total > 0 ? round($items->sum('amount') / $total * 100, 1) : 0,
            ])
            ->sortByDesc('total')
            ->values();

        $start = Carbon::parse($validated['start']);
        $end = Carbon::parse($validated['end']);

        return response()->json([
            'title' => $start->isSameDay($end) ? $start->format('D, d M Y') : $start->format('d M').' – '.$end->format('d M Y'),
            'total' => $total,
            'categories' => $categories,
            'expenses' => $expenses->values(),
        ]);
    }

    /**
     * Build the spending chart buckets for a range with one grouped query
     * (previously one query per day).
     *
     * @return array{labels: array<int, string>, data: array<int, float>, ranges: array<int, array{start: string, end: string}>, title: string, sub: string}
     */
    private function buildSpendingChart(int $userId, string $range, Request $request, bool $includePersonal): array
    {
        $today = Carbon::today();
        $monthly = false;

        if ($range === 'week') {
            $refDate = $request->filled('date') ? Carbon::parse($request->get('date')) : $today;
            $start = $refDate->copy()->startOfWeek();
            $end = $start->copy()->endOfWeek()->startOfDay();
            $title = 'This Week Spending';
            $sub = $start->format('d M').' - '.$end->format('d M Y');
            $labelFormat = 'D (d M)';
        } elseif ($range === 'month') {
            $start = Carbon::parse($request->get('month', $today->format('Y-m')).'-01')->startOfMonth();
            $end = $start->copy()->endOfMonth()->startOfDay();
            $title = $start->format('F Y').' Daily Spending';
            $sub = $start->format('01 M').' - '.$end->format('d M Y');
            $labelFormat = 'd M';
        } elseif ($range === 'custom') {
            $start = Carbon::parse($request->get('start_date', $today->copy()->subDays(14)->toDateString()))->startOfDay();
            $end = Carbon::parse($request->get('end_date', $today->toDateString()))->startOfDay();
            if ($start->gt($end)) {
                [$start, $end] = [$end, $start];
            }
            // Keep the chart readable and the work bounded
            if ($start->diffInDays($end) > 366) {
                $start = $end->copy()->subDays(366);
            }
            $title = 'Custom Range Spending';
            $sub = $start->format('d M Y').' - '.$end->format('d M Y');
            $labelFormat = $start->diffInDays($end) > 31 ? 'd M' : 'D, d M';
        } elseif ($range === 'year') {
            $year = (int) $request->get('year', $today->year);
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end = Carbon::create($year, 12, 31)->startOfDay();
            $title = $year.' Monthly Spending';
            $sub = 'Jan '.$year.' - Dec '.$year;
            $labelFormat = 'M Y';
            $monthly = true;
        } else {
            $start = $today->copy()->subDays(6);
            $end = $today->copy();
            $title = '7-Day Spending';
            $sub = 'Daily expenses breakdown';
            $labelFormat = 'D, M j';
        }

        $dailyTotals = Expense::where('user_id', $userId)
            ->whereNull('parent_id')
            ->whereDate('date', '>=', $start->toDateString())->whereDate('date', '<=', $end->toDateString())
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->get(['date', 'amount', 'gst_amount'])
            ->groupBy(fn (Expense $expense) => $expense->date->toDateString())
            ->map(fn ($rows) => $rows->sum(fn ($row) => (float) $row->amount + (float) ($row->gst_amount ?? 0)))
            ->all();

        if ($includePersonal) {
            PersonalExpense::where('user_id', $userId)
                ->whereDate('date', '>=', $start->toDateString())->whereDate('date', '<=', $end->toDateString())
                ->where('is_archived', false)
                ->get(['date', 'amount'])
                ->each(function (PersonalExpense $expense) use (&$dailyTotals): void {
                    $key = $expense->date->toDateString();
                    $dailyTotals[$key] = ($dailyTotals[$key] ?? 0) + (float) $expense->amount;
                });
        }

        $labels = [];
        $data = [];
        $ranges = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $bucketEnd = $monthly ? $cursor->copy()->endOfMonth()->startOfDay() : $cursor->copy();
            $sum = 0.0;
            for ($day = $cursor->copy(); $day->lte($bucketEnd); $day->addDay()) {
                $sum += (float) ($dailyTotals[$day->toDateString()] ?? 0);
            }

            $labels[] = $cursor->format($labelFormat);
            $data[] = round($sum, 2);
            $ranges[] = ['start' => $cursor->toDateString(), 'end' => $bucketEnd->toDateString()];
            $cursor = $bucketEnd->copy()->addDay();
        }

        return compact('labels', 'data', 'ranges', 'title', 'sub');
    }
}
