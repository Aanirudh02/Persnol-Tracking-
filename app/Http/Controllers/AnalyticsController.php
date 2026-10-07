<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FoodEntry;
use App\Models\FuelEntry;
use App\Models\Income;
use App\Models\Mistake;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\ScooterTrip;
use App\Services\CloudinaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $expenseType = $request->get('expense_type', 'normal');
        if (! in_array($expenseType, ['normal', 'personal'], true)) {
            $expenseType = 'normal';
        }

        $dateFilter = $this->resolveDateRange($request);
        $startDate = $dateFilter['start_date'];
        $endDate = $dateFilter['end_date'];
        $period = $dateFilter['period'];
        $days = $dateFilter['days_count'];

        // 1. Finance Analytics
        if ($expenseType === 'personal') {
            $expensesByDay = PersonalExpense::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('date, sum(amount) as total, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $categorySpending = PersonalExpense::where('personal_expenses.user_id', $user->id)
                ->whereBetween('personal_expenses.date', [$startDate, $endDate])
                ->leftJoin('personal_expense_categories', 'personal_expenses.category_id', '=', 'personal_expense_categories.id')
                ->selectRaw("coalesce(personal_expense_categories.id, 0) as category_id, coalesce(personal_expense_categories.name, 'Uncategorized') as name, coalesce(personal_expense_categories.color, '#ec4899') as color, coalesce(personal_expense_categories.icon, '🛍️') as icon, count(personal_expenses.id) as count, sum(personal_expenses.amount) as total")
                ->groupBy('personal_expense_categories.id', 'personal_expense_categories.name', 'personal_expense_categories.color', 'personal_expense_categories.icon')
                ->orderByDesc('total')
                ->get();

            $paymentMethods = PersonalExpense::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('payment_method, sum(amount) as total')
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->pluck('total', 'payment_method')
                ->toArray();

            $rawTransactions = PersonalExpense::where('personal_expenses.user_id', $user->id)
                ->whereBetween('personal_expenses.date', [$startDate, $endDate])
                ->leftJoin('personal_expense_categories', 'personal_expenses.category_id', '=', 'personal_expense_categories.id')
                ->selectRaw("personal_expenses.id, personal_expenses.date, personal_expenses.amount, personal_expenses.description, coalesce(personal_expense_categories.name, 'Uncategorized') as category_name, coalesce(personal_expense_categories.color, '#ec4899') as category_color")
                ->orderBy('personal_expenses.date')
                ->get();
        } else {
            $expensesByDay = Expense::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNull('parent_id')
                ->selectRaw('date, sum(amount + gst_amount) as total, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $categorySpending = Expense::where('expenses.user_id', $user->id)
                ->whereBetween('expenses.date', [$startDate, $endDate])
                ->whereNull('expenses.parent_id')
                ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                ->selectRaw("coalesce(expense_categories.id, 0) as category_id, coalesce(expense_categories.name, 'Uncategorized') as name, coalesce(expense_categories.color, '#6366f1') as color, coalesce(expense_categories.icon, '🏷️') as icon, count(expenses.id) as count, sum(expenses.amount + expenses.gst_amount) as total")
                ->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color', 'expense_categories.icon')
                ->orderByDesc('total')
                ->get();

            $paymentMethods = Expense::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereNull('parent_id')
                ->selectRaw('payment_method, sum(amount + gst_amount) as total')
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->pluck('total', 'payment_method')
                ->toArray();

            $rawTransactions = Expense::where('expenses.user_id', $user->id)
                ->whereBetween('expenses.date', [$startDate, $endDate])
                ->whereNull('expenses.parent_id')
                ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
                ->selectRaw("expenses.id, expenses.date, (expenses.amount + expenses.gst_amount) as amount, expenses.description, coalesce(expense_categories.name, 'Uncategorized') as category_name, coalesce(expense_categories.color, '#6366f1') as category_color")
                ->orderBy('expenses.date')
                ->get();
        }

        $totalPeriodSpending = (float) $categorySpending->sum('total');
        $totalTransactionsCount = (int) $categorySpending->sum('count');

        foreach ($categorySpending as $cat) {
            $cat->percentage = $totalPeriodSpending > 0 ? round(($cat->total / $totalPeriodSpending) * 100, 1) : 0;
            $cat->average = $cat->count > 0 ? round($cat->total / $cat->count, 2) : 0;
        }

        $expensesByDayMap = $expensesByDay->pluck('total', 'date')->toArray();

        // Statistical Distribution (Histogram & Frequency Polygon Bins)
        $binsDefinition = [
            ['label' => 'Under ₹100', 'min' => 0, 'max' => 99.99],
            ['label' => '₹100–₹250', 'min' => 100, 'max' => 249.99],
            ['label' => '₹250–₹500', 'min' => 250, 'max' => 499.99],
            ['label' => '₹500–₹1K', 'min' => 500, 'max' => 999.99],
            ['label' => '₹1K–₹2.5K', 'min' => 1000, 'max' => 2499.99],
            ['label' => '₹2.5K–₹5K', 'min' => 2500, 'max' => 4999.99],
            ['label' => 'Above ₹5K', 'min' => 5000, 'max' => 999999999],
        ];

        $histogramData = [];
        foreach ($binsDefinition as $bin) {
            $matching = $rawTransactions->filter(function ($t) use ($bin) {
                $amt = (float) $t->amount;

                return $amt >= $bin['min'] && $amt <= $bin['max'];
            });
            $histogramData[] = [
                'label' => $bin['label'],
                'min' => $bin['min'],
                'max' => $bin['max'],
                'count' => $matching->count(),
                'total' => round((float) $matching->sum('amount'), 2),
            ];
        }

        // Scatter Plot items
        $scatterPoints = $rawTransactions->map(function ($t) use ($startDate) {
            $parsedDate = Carbon::parse($t->date);

            return [
                'x' => $parsedDate->diffInDays(Carbon::parse($startDate)) + 1,
                'y' => round((float) $t->amount, 2),
                'date' => $parsedDate->format('d M Y'),
                'description' => $t->description ?? 'Expense',
                'category' => $t->category_name,
                'color' => $t->category_color,
                'id' => $t->id,
            ];
        })->values()->toArray();

        // Box plot stats (Min, Q1, Median, Q3, Max)
        $amounts = $rawTransactions->pluck('amount')->map(fn ($a) => (float) $a)->sort()->values()->toArray();
        $amtCount = count($amounts);
        $boxPlotStats = [
            'min' => $amtCount > 0 ? round($amounts[0], 2) : 0,
            'max' => $amtCount > 0 ? round($amounts[$amtCount - 1], 2) : 0,
            'median' => $amtCount > 0 ? round($amounts[(int) floor($amtCount * 0.5)], 2) : 0,
            'q1' => $amtCount > 0 ? round($amounts[(int) floor($amtCount * 0.25)], 2) : 0,
            'q3' => $amtCount > 0 ? round($amounts[(int) floor($amtCount * 0.75)], 2) : 0,
            'avg' => $amtCount > 0 ? round(array_sum($amounts) / $amtCount, 2) : 0,
        ];

        // Cumulative spending by day
        $cumulativeTimeline = [];
        $runningSum = 0;
        foreach ($expensesByDay as $dayItem) {
            $runningSum += (float) $dayItem->total;
            $cumulativeTimeline[] = [
                'date' => Carbon::parse($dayItem->date)->format('d M'),
                'raw_date' => $dayItem->date,
                'daily' => (float) $dayItem->total,
                'cumulative' => round($runningSum, 2),
            ];
        }

        $incomeByDay = Income::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, sum(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // 2. Food & Snacks Analytics
        $snackTrend = FoodEntry::where('user_id', $user->id)
            ->where('is_snack', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, count(*) as count, sum(amount + gst_amount) as total_spent')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topSnacks = FoodEntry::where('user_id', $user->id)
            ->where('is_snack', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('item_name, count(*) as count, sum(amount + gst_amount) as total_spent')
            ->groupBy('item_name')
            ->orderByDesc('count')
            ->take(6)
            ->get();

        // 3. Scooter Analytics
        $fuelMonthExpression = DB::connection()->getDriverName() === 'pgsql'
            ? "to_char(date, 'YYYY-MM')"
            : 'DATE_FORMAT(date, "%Y-%m")';
        $scooterDistanceByDay = ScooterTrip::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, sum(distance_km) as total_km, count(*) as trips')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $petrolByMonth = FuelEntry::where('user_id', $user->id)
            ->selectRaw("{$fuelMonthExpression} as month, sum(amount) as total_spent, sum(litres) as total_litres")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 4. Life & Habits Analytics
        $sleepRecords = DailyRecord::where('user_id', $user->id)
            ->whereBetween('record_date', [$startDate, $endDate])
            ->whereNotNull('wake_up_time')
            ->orderBy('record_date')
            ->get(['record_date', 'wake_up_time', 'sleep_time', 'sleep_duration_hours', 'sleep_quality', 'day_rating']);

        $avgSleepDuration = round($sleepRecords->avg('sleep_duration_hours') ?? 0, 1);
        $avgDayRating = round($sleepRecords->avg('day_rating') ?? 0, 1);

        $activityCountByCategory = Activity::where('activities.user_id', $user->id)
            ->whereBetween('activities.date', [$startDate, $endDate])
            ->join('activity_categories', 'activities.category_id', '=', 'activity_categories.id')
            ->selectRaw('activity_categories.name, count(*) as count')
            ->groupBy('activity_categories.name')
            ->orderByDesc('count')
            ->get();

        $mistakesTrend = Mistake::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return view('analytics.index', compact(
            'dateFilter',
            'days',
            'period',
            'expenseType',
            'expensesByDay',
            'expensesByDayMap',
            'totalPeriodSpending',
            'totalTransactionsCount',
            'incomeByDay',
            'categorySpending',
            'paymentMethods',
            'histogramData',
            'scatterPoints',
            'boxPlotStats',
            'cumulativeTimeline',
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

    /**
     * Get a group of expenses for a specific category, date, or price bin for drilldown modal.
     */
    public function drilldown(Request $request)
    {
        $user = $request->user();
        $type = $request->get('type', 'normal');
        $categoryId = $request->get('category_id');
        $date = $request->get('date');
        $binMin = $request->get('bin_min');
        $binMax = $request->get('bin_max');

        $dateFilter = $this->resolveDateRange($request);
        $startDate = $dateFilter['start_date'];
        $endDate = $dateFilter['end_date'];

        if ($type === 'personal') {
            $query = PersonalExpense::where('user_id', $user->id)
                ->with('category');

            if ($date) {
                $query->whereDate('date', $date);
                $title = 'Personal Expenses on '.Carbon::parse($date)->format('d M Y');
                $color = '#ec4899';
            } elseif ($binMin !== null && $binMax !== null) {
                $query->whereBetween('date', [$startDate, $endDate])
                    ->where('amount', '>=', (float) $binMin)
                    ->where('amount', '<=', (float) $binMax);
                $title = 'Personal Expenses: ₹'.number_format((float) $binMin).' - ₹'.number_format((float) $binMax);
                $color = '#ec4899';
            } elseif ($categoryId !== null) {
                if ((int) $categoryId === 0) {
                    $query->whereNull('category_id');
                    $title = 'Uncategorized Personal Expenses';
                    $color = '#94a3b8';
                } else {
                    $query->where('category_id', $categoryId);
                    $category = PersonalExpenseCategory::find($categoryId);
                    $title = ($category?->name ?? 'Personal Category').' Expenses';
                    $color = $category?->color ?? '#ec4899';
                }
                $query->whereBetween('date', [$startDate, $endDate]);
            } else {
                $query->whereBetween('date', [$startDate, $endDate]);
                $title = 'Personal Expenses ('.$dateFilter['label'].')';
                $color = '#ec4899';
            }

            $items = $query->orderByDesc('date')->orderByDesc('id')->get();
            $total = $items->sum('amount');

            $expenses = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->date ? $item->date->format('d M Y') : '—',
                    'raw_date' => $item->date ? $item->date->toDateString() : '',
                    'description' => $item->description ?? 'Personal Expense',
                    'category' => $item->category?->name ?? 'Uncategorized',
                    'category_color' => $item->category?->color ?? '#ec4899',
                    'amount' => (float) $item->amount,
                    'amount_formatted' => '₹'.number_format($item->amount, 2),
                    'payment_method' => $item->payment_method ?? '—',
                    'paid_by' => 'Me',
                    'friend_splits' => null,
                    'notes' => $item->notes,
                    'receipt_url' => null,
                    'view_url' => route('personal-expenses.show', $item),
                ];
            });

            return response()->json([
                'type' => 'personal',
                'title' => $title,
                'period_label' => $dateFilter['label'],
                'color' => $color,
                'total' => (float) $total,
                'total_formatted' => '₹'.number_format($total, 2),
                'count' => $items->count(),
                'expenses' => $expenses,
                'categories' => $this->categoryBreakdown($expenses),
            ]);
        }

        // Normal Expenses
        $query = Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->with(['category', 'friendSplits.friend']);

        if ($date) {
            $query->whereDate('date', $date);
            $title = 'Expenses on '.Carbon::parse($date)->format('d M Y');
            $color = '#6366f1';
        } elseif ($binMin !== null && $binMax !== null) {
            $query->whereBetween('date', [$startDate, $endDate])
                ->whereRaw('(amount + coalesce(gst_amount, 0)) >= ?', [(float) $binMin])
                ->whereRaw('(amount + coalesce(gst_amount, 0)) <= ?', [(float) $binMax]);
            $title = 'Expenses: ₹'.number_format((float) $binMin).' - ₹'.number_format((float) $binMax);
            $color = '#6366f1';
        } elseif ($categoryId !== null) {
            if ((int) $categoryId === 0) {
                $query->whereNull('category_id');
                $title = 'Uncategorized Expenses';
                $color = '#94a3b8';
            } else {
                $query->where('category_id', $categoryId);
                $category = ExpenseCategory::find($categoryId);
                $title = ($category?->name ?? 'Category').' Expenses';
                $color = $category?->color ?? '#6366f1';
            }
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            $query->whereBetween('date', [$startDate, $endDate]);
            $title = 'Normal Expenses ('.$dateFilter['label'].')';
            $color = '#6366f1';
        }

        $items = $query->orderByDesc('date')->orderByDesc('id')->get();
        $total = $items->sum(fn ($e) => (float) $e->amount + (float) ($e->gst_amount ?? 0));

        $expenses = $items->map(function ($item) {
            $splitsText = null;
            if ($item->friendSplits && $item->friendSplits->isNotEmpty()) {
                $splitsText = 'Split: '.$item->friendSplits->map(fn ($s) => ($s->friend?->name ?? 'Friend').' (₹'.number_format($s->friend_share, 2).')')->implode(', ');
            }

            return [
                'id' => $item->id,
                'date' => $item->date ? $item->date->format('d M Y') : '—',
                'raw_date' => $item->date ? $item->date->toDateString() : '',
                'description' => $item->description ?? 'Expense',
                'category' => $item->category?->name ?? 'Uncategorized',
                'category_color' => $item->category?->color ?? '#6366f1',
                'amount' => (float) $item->amount + (float) ($item->gst_amount ?? 0),
                'amount_formatted' => '₹'.number_format((float) $item->amount + (float) ($item->gst_amount ?? 0), 2),
                'payment_method' => $item->payment_method ?? '—',
                'paid_by' => $item->paid_by ?? 'Me',
                'friend_splits' => $splitsText,
                'notes' => $item->notes,
                'receipt_url' => $item->receipt_image ? CloudinaryService::url($item->receipt_image) : null,
                'view_url' => route('expenses.show', $item),
            ];
        });

        return response()->json([
            'type' => 'normal',
            'title' => $title,
            'period_label' => $dateFilter['label'],
            'color' => $color,
            'total' => (float) $total,
            'total_formatted' => '₹'.number_format($total, 2),
            'count' => $items->count(),
            'expenses' => $expenses,
            'categories' => $this->categoryBreakdown($expenses),
        ]);
    }

    /**
     * Resolve date range filter from query parameters.
     */
    private function resolveDateRange(Request $request): array
    {
        $period = $request->get('period');

        if (! $period && $request->has('days')) {
            $daysParam = (int) $request->get('days');
            if ($daysParam === 1) {
                $period = 'today';
            } elseif ($daysParam === 7) {
                $period = 'week';
            } elseif ($daysParam === 365) {
                $period = 'year';
            } else {
                $period = 'custom';
            }
        }

        if (! $period) {
            $period = 'month';
        }

        $today = Carbon::today();
        $monthVal = $request->get('month', $today->format('Y-m'));
        $yearVal = (int) $request->get('year', $today->year);
        $fromMonth = $request->get('from_month', $today->copy()->subMonths(2)->format('Y-m'));
        $toMonth = $request->get('to_month', $today->format('Y-m'));
        $fromDate = $request->get('from_date', $today->copy()->subDays(30)->toDateString());
        $toDate = $request->get('to_date', $today->toDateString());

        switch ($period) {
            case 'today':
                $startDate = $today->toDateString();
                $endDate = $today->toDateString();
                $label = 'Today ('.$today->format('d M Y').')';
                break;

            case 'week':
                $startDate = $today->copy()->subDays(6)->toDateString();
                $endDate = $today->toDateString();
                $label = Carbon::parse($startDate)->format('d M').' – '.Carbon::parse($endDate)->format('d M Y').' (7D)';
                break;

            case 'month':
                try {
                    $startCarbon = Carbon::createFromFormat('Y-m', $monthVal)->startOfMonth();
                } catch (\Exception $e) {
                    $startCarbon = $today->copy()->startOfMonth();
                    $monthVal = $startCarbon->format('Y-m');
                }
                $endCarbon = $startCarbon->copy()->endOfMonth();
                $startDate = $startCarbon->toDateString();
                $endDate = $endCarbon->toDateString();
                $label = $startCarbon->format('F Y');
                break;

            case 'multi_month':
                try {
                    $startCarbon = Carbon::createFromFormat('Y-m', $fromMonth)->startOfMonth();
                } catch (\Exception $e) {
                    $startCarbon = $today->copy()->subMonths(2)->startOfMonth();
                    $fromMonth = $startCarbon->format('Y-m');
                }
                try {
                    $endCarbon = Carbon::createFromFormat('Y-m', $toMonth)->endOfMonth();
                } catch (\Exception $e) {
                    $endCarbon = $today->copy()->endOfMonth();
                    $toMonth = $endCarbon->format('Y-m');
                }
                if ($startCarbon->gt($endCarbon)) {
                    $temp = $startCarbon;
                    $startCarbon = $endCarbon->copy()->startOfMonth();
                    $endCarbon = $temp->copy()->endOfMonth();
                }
                $startDate = $startCarbon->toDateString();
                $endDate = $endCarbon->toDateString();
                $label = $startCarbon->format('M Y').' – '.$endCarbon->format('M Y');
                break;

            case 'year':
                if ($yearVal < 2000 || $yearVal > 2100) {
                    $yearVal = $today->year;
                }
                $startDate = Carbon::createFromDate($yearVal, 1, 1)->startOfYear()->toDateString();
                $endDate = Carbon::createFromDate($yearVal, 12, 31)->endOfYear()->toDateString();
                $label = 'Year '.$yearVal;
                break;

            case 'custom':
            default:
                $period = 'custom';
                try {
                    $startCarbon = Carbon::parse($fromDate);
                } catch (\Exception $e) {
                    $startCarbon = $today->copy()->subDays(30);
                }
                try {
                    $endCarbon = Carbon::parse($toDate);
                } catch (\Exception $e) {
                    $endCarbon = $today->copy();
                }
                if ($startCarbon->gt($endCarbon)) {
                    $temp = $startCarbon;
                    $startCarbon = $endCarbon;
                    $endCarbon = $temp;
                }
                $startDate = $startCarbon->toDateString();
                $endDate = $endCarbon->toDateString();
                $label = $startCarbon->format('d M Y').' – '.$endCarbon->format('d M Y');
                break;
        }

        $daysDiff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;

        return [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $label,
            'days_count' => $daysDiff,
            'month_val' => $monthVal,
            'from_month' => $fromMonth,
            'to_month' => $toMonth,
            'year_val' => $yearVal,
            'from_date' => $startDate,
            'to_date' => $endDate,
        ];
    }

    /**
     * Group drilldown rows by category so a clicked day/bin shows its categories.
     *
     * @param  Collection<int, array<string, mixed>>  $expenses
     * @return Collection<int, array{name: string, color: string, count: int, total: float, percentage: float}>
     */
    private function categoryBreakdown(Collection $expenses): Collection
    {
        $grandTotal = (float) $expenses->sum('amount');

        return $expenses->groupBy('category')
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'color' => $rows->first()['category_color'],
                'count' => $rows->count(),
                'total' => round((float) $rows->sum('amount'), 2),
                'percentage' => $grandTotal > 0 ? round($rows->sum('amount') / $grandTotal * 100, 1) : 0,
            ])
            ->sortByDesc('total')
            ->values();
    }
}
