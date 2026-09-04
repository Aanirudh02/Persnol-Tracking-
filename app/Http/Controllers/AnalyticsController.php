<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Income;
use App\Models\FoodEntry;
use App\Models\ScooterTrip;
use App\Models\FuelEntry;
use App\Models\DailyRecord;
use App\Models\Mistake;
use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $days = (int) $request->get('days', 30);
        $startDate = Carbon::today()->subDays($days)->toDateString();

        // 1. Finance Analytics
        $expensesByDay = Expense::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->selectRaw('date, sum(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $incomeByDay = Income::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->selectRaw('date, sum(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $categorySpending = Expense::where('expenses.user_id', $user->id)
            ->where('expenses.date', '>=', $startDate)
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name, sum(expenses.amount) as total, expense_categories.color')
            ->groupBy('expense_categories.name', 'expense_categories.color')
            ->orderByDesc('total')
            ->get();

        $paymentMethods = Expense::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->selectRaw('payment_method, sum(amount) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->pluck('total', 'payment_method')
            ->toArray();

        // 2. Food & Snacks Analytics
        $snackTrend = FoodEntry::where('user_id', $user->id)
            ->where('is_snack', true)
            ->where('date', '>=', $startDate)
            ->selectRaw('date, count(*) as count, sum(amount) as total_spent')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topSnacks = FoodEntry::where('user_id', $user->id)
            ->where('is_snack', true)
            ->where('date', '>=', $startDate)
            ->selectRaw('item_name, count(*) as count, sum(amount) as total_spent')
            ->groupBy('item_name')
            ->orderByDesc('count')
            ->take(6)
            ->get();

        // 3. Scooter Analytics
        $scooterDistanceByDay = ScooterTrip::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->selectRaw('date, sum(distance_km) as total_km, count(*) as trips')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $petrolByMonth = FuelEntry::where('user_id', $user->id)
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month, sum(amount) as total_spent, sum(litres) as total_litres')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 4. Life & Habits Analytics
        $sleepRecords = DailyRecord::where('user_id', $user->id)
            ->where('record_date', '>=', $startDate)
            ->whereNotNull('wake_up_time')
            ->orderBy('record_date')
            ->get(['record_date', 'wake_up_time', 'sleep_time', 'sleep_duration_hours', 'sleep_quality', 'day_rating']);

        $avgSleepDuration = round($sleepRecords->avg('sleep_duration_hours') ?? 0, 1);
        $avgDayRating = round($sleepRecords->avg('day_rating') ?? 0, 1);

        $activityCountByCategory = Activity::where('activities.user_id', $user->id)
            ->where('activities.date', '>=', $startDate)
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->selectRaw('activity_categories.name, count(*) as count')
            ->groupBy('activity_categories.name')
            ->orderByDesc('count')
            ->get();

        $mistakesTrend = Mistake::where('user_id', $user->id)
            ->where('date', '>=', $startDate)
            ->selectRaw('date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return view('analytics.index', compact(
            'days',
            'expensesByDay',
            'incomeByDay',
            'categorySpending',
            'paymentMethods',
            'snackTrend',
            'topSnacks',
            'scooterDistanceByDay',
            'petrolByMonth',
            'sleepRecords',
            'avgSleepDuration',
            'avgDayRating',
            'activityCountByCategory',
            'mistakesTrend'
        ));
    }
}
