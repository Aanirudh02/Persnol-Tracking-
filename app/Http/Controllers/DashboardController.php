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
        \App\Services\DailyRegisterService $dailyRegisterService
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

        if (! $dayRecord) {
            $dayRecord = DailyRecord::create([
                'user_id' => $user->id,
                'record_date' => $date,
            ]);
        }

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
                $expensesTotal += (float) PersonalExpense::where('user_id', $user->id)->where('date', $date)->sum('amount');
            }
            $snacksCount = $workings['snack_count'];
            $petrolSpent = $workings['fuel'];
            $activitiesCount = Activity::where('user_id', $user->id)->where('date', $date)->count();
            $scooterTripsCount = ScooterTrip::where('user_id', $user->id)->where('date', $date)->count();
            $scooterDistanceKm = (float) ScooterTrip::where('user_id', $user->id)->where('date', $date)->sum('distance_km');
            $mistakesCount = Mistake::where('user_id', $user->id)->where('date', $date)->count();
        } else {
            $moneyReceived = (float) Income::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->sum('amount');
            $expensesTotal = (float) Expense::where('user_id', $user->id)->whereNull('parent_id')->whereBetween('date', [$startDate, $endDate])->sum(DB::raw('amount + gst_amount'));
            if ($showPersonalInDashboard) {
                $expensesTotal += (float) PersonalExpense::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->sum('amount');
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

        $last7Days = [];
        $expenseChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $dateStr = $d->toDateString();
            $last7Days[] = $d->format('D, M j');
            $daySum = (float) Expense::where('user_id', $user->id)
                ->whereNull('parent_id')
                ->where('date', $dateStr)
                ->sum(DB::raw('amount + gst_amount'));

            if ($showPersonalInDashboard) {
                $daySum += (float) PersonalExpense::where('user_id', $user->id)
                    ->where('date', $dateStr)
                    ->sum('amount');
            }
            $expenseChartData[] = $daySum;
        }

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
        $user = $request->user();
        $range = $request->get('range', '7d');
        $labels = [];
        $data = [];
        $title = 'Spending Trend';
        $sub = '';

        if ($range === 'week') {
            $refDate = $request->filled('date') ? Carbon::parse($request->get('date')) : Carbon::today();
            $start = $refDate->copy()->startOfWeek();
            $title = 'This Week Spending';
            $sub = $start->format('d M').' - '.$start->copy()->endOfWeek()->format('d M Y');
            for ($i = 0; $i < 7; $i++) {
                $d = $start->copy()->addDays($i);
                $labels[] = $d->format('D (d M)');
                $sum = (float) Expense::where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->where('date', $d->toDateString())
                    ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
                    ->sum(DB::raw('amount + gst_amount'));
                $data[] = $sum;
            }
        } elseif ($range === 'month') {
            $targetMonth = $request->get('month', Carbon::today()->format('Y-m'));
            $start = Carbon::parse($targetMonth.'-01')->startOfMonth();
            $daysInMonth = $start->daysInMonth;
            $title = $start->format('F Y').' Daily Spending';
            $sub = $start->format('01 M').' - '.$start->copy()->endOfMonth()->format('d M Y');
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $d = $start->copy()->day($i);
                $labels[] = $d->format('d M');
                $sum = (float) Expense::where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->where('date', $d->toDateString())
                    ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
                    ->sum(DB::raw('amount + gst_amount'));
                $data[] = $sum;
            }
        } elseif ($range === 'custom') {
            $start = Carbon::parse($request->get('start_date', Carbon::today()->subDays(14)->toDateString()));
            $end = Carbon::parse($request->get('end_date', Carbon::today()->toDateString()));
            if ($start->gt($end)) {
                [$start, $end] = [$end, $start];
            }
            $diffDays = $start->diffInDays($end);
            $title = 'Custom Range Spending';
            $sub = $start->format('d M Y').' - '.$end->format('d M Y');

            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $labels[] = $diffDays > 31 ? $cursor->format('d M') : $cursor->format('D, d M');
                $sum = (float) Expense::where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->where('date', $cursor->toDateString())
                    ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
                    ->sum(DB::raw('amount + gst_amount'));
                $data[] = $sum;
                $cursor->addDay();
            }
        } elseif ($range === 'year') {
            $year = (int) $request->get('year', Carbon::today()->year);
            $title = $year.' Monthly Spending';
            $sub = 'Jan '.$year.' - Dec '.$year;
            for ($m = 1; $m <= 12; $m++) {
                $d = Carbon::create($year, $m, 1)->startOfMonth();
                $labels[] = $d->format('M Y');
                $sum = (float) Expense::where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->whereBetween('date', [$d->toDateString(), $d->copy()->endOfMonth()->toDateString()])
                    ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
                    ->sum(DB::raw('amount + gst_amount'));
                $data[] = $sum;
            }
        } else {
            // Default 7 days
            $title = '7-Day Spending';
            $sub = 'Daily expenses breakdown';
            for ($i = 6; $i >= 0; $i--) {
                $d = Carbon::today()->subDays($i);
                $labels[] = $d->format('D, M j');
                $sum = (float) Expense::where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->where('date', $d->toDateString())
                    ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
                    ->sum(DB::raw('amount + gst_amount'));
                $data[] = $sum;
            }
        }

        return response()->json([
            'range' => $range,
            'labels' => $labels,
            'data' => $data,
            'total' => round(array_sum($data), 2),
            'title' => $title,
            'sub' => $sub.' (Total: ₹'.number_format(array_sum($data), 2).')',
        ]);
    }
}
