<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\FoodEntry;
use App\Models\FuelEntry;
use App\Models\Income;
use App\Models\Mistake;
use App\Models\ScooterTrip;
use App\Services\DailyPromptService;
use App\Services\DayTimelineService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        DailyPromptService $promptService,
        DayTimelineService $timelineService,
        WalletService $walletService
    ) {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $date = $request->get('date', $today);
        if ($date > $today) {
            $date = $today;
        }

        $period = $request->get('period', 'today');
        if (! in_array($period, ['today', 'week', 'month'], true)) {
            $period = 'today';
        }

        $startDate = match ($period) {
            'week' => Carbon::parse($date)->startOfWeek()->toDateString(),
            'month' => Carbon::parse($date)->startOfMonth()->toDateString(),
            default => $date,
        };
        $endDate = $date;

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

        if ($period === 'today') {
            $moneyReceived = $workings['income'];
            $expensesTotal = $workings['expenses'];
            $snacksCount = $workings['snack_count'];
            $petrolSpent = $workings['fuel'];
            $activitiesCount = Activity::where('user_id', $user->id)->where('date', $date)->count();
            $scooterTripsCount = ScooterTrip::where('user_id', $user->id)->where('date', $date)->count();
            $scooterDistanceKm = (float) ScooterTrip::where('user_id', $user->id)->where('date', $date)->sum('distance_km');
            $mistakesCount = Mistake::where('user_id', $user->id)->where('date', $date)->count();
        } else {
            $moneyReceived = (float) Income::where('user_id', $user->id)->whereBetween('date', [$startDate, $endDate])->sum('amount');
            $expensesTotal = (float) Expense::where('user_id', $user->id)->whereNull('parent_id')->whereBetween('date', [$startDate, $endDate])->sum(DB::raw('amount + gst_amount'));
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
            $expenseChartData[] = (float) Expense::where('user_id', $user->id)
                ->whereNull('parent_id')
                ->where('date', $dateStr)
                ->sum(DB::raw('amount + gst_amount'));
        }

        $prevDate = Carbon::parse($date)->subDay()->toDateString();
        $nextDate = Carbon::parse($date)->addDay()->toDateString();
        if ($nextDate > $today) {
            $nextDate = $today;
        }
        $isToday = $date === $today;
        $todayRecord = $dayRecord;

        return view('dashboard.index', compact(
            'todayRecord',
            'yesterdayRecord',
            'moneyReceived',
            'expensesTotal',
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
            'startDate',
            'endDate',
            'wallets',
            'currentBalance'
        ));
    }
}
