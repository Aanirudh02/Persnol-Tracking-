<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PersonalExpense;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AllExpensesController extends Controller
{
    public function index(Request $request, OptionsService $options): View
    {
        $user = $request->user();
        $period = $request->get('period', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $typeFilter = $request->get('type', 'all'); // 'all', 'normal', 'personal', 'voluntary', 'archived'

        [$startDate, $endDate] = $this->resolveDates($period, $fromDate, $toDate);

        // 1. Normal Expenses query
        $normalQuery = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->with('category');

        if ($startDate && $endDate) {
            $normalQuery->whereBetween('date', [$startDate, $endDate]);
        }

        if ($typeFilter === 'voluntary') {
            $normalQuery->where('is_voluntary', true);
        } elseif ($typeFilter === 'archived') {
            $normalQuery->whereHas('category', fn ($q) => $q->where('is_archived', true));
        }

        $normalExpenses = $normalQuery->orderByDesc('date')->orderByDesc('created_at')->get();

        // 2. Personal Expenses query
        $personalExpenses = collect();
        if ($typeFilter === 'all' || $typeFilter === 'personal' || $typeFilter === 'voluntary') {
            $pQuery = PersonalExpense::query()
                ->where('user_id', $user->id)
                ->with('category');

            if ($startDate && $endDate) {
                $pQuery->whereBetween('date', [$startDate, $endDate]);
            }

            if ($typeFilter === 'voluntary') {
                $pQuery->where('is_voluntary', true);
            }

            $personalExpenses = $pQuery->orderByDesc('date')->orderByDesc('created_at')->get();
        }

        // Totals
        $normalTotal = (float) $normalExpenses->sum(fn ($e) => $e->totalAmount());
        $personalTotal = (float) $personalExpenses->sum('amount');
        $combinedTotal = round($normalTotal + $personalTotal, 2);

        $voluntaryTotal = round(
            $normalExpenses->where('is_voluntary', true)->sum(fn ($e) => $e->totalAmount()) +
            $personalExpenses->where('is_voluntary', true)->sum('amount'),
            2
        );

        // Standardized merged stream for list display
        $mergedStream = collect();

        if ($typeFilter !== 'personal') {
            foreach ($normalExpenses as $exp) {
                $mergedStream->push([
                    'id' => $exp->id,
                    'item_type' => 'normal',
                    'date' => $exp->date->toDateString(),
                    'description' => $exp->description ?: ($exp->category?->name ?? 'Expense'),
                    'category' => $exp->category?->name ?? 'Uncategorized',
                    'category_color' => $exp->category?->color ?? '#3b82f6',
                    'amount' => $exp->totalAmount(),
                    'payment_method' => $exp->payment_method ?: 'Cash',
                    'is_voluntary' => $exp->is_voluntary,
                    'is_archived' => (bool) ($exp->category?->is_archived),
                    'model' => $exp,
                ]);
            }
        }

        if ($typeFilter !== 'normal' && $typeFilter !== 'archived') {
            foreach ($personalExpenses as $pexp) {
                $mergedStream->push([
                    'id' => $pexp->id,
                    'item_type' => 'personal',
                    'date' => $pexp->date->toDateString(),
                    'description' => $pexp->description ?: ($pexp->category?->name ?? 'Personal Expense'),
                    'category' => $pexp->category?->name ?? 'Personal',
                    'category_color' => $pexp->category?->color ?? '#ec4899',
                    'amount' => (float) $pexp->amount,
                    'payment_method' => $pexp->payment_method ?: 'Cash',
                    'is_voluntary' => $pexp->is_voluntary,
                    'is_archived' => (bool) ($pexp->category?->is_archived),
                    'model' => $pexp,
                ]);
            }
        }

        $allTransactions = $mergedStream->sortByDesc('date')->values();

        // Category breakdown
        $categoryBreakdown = $allTransactions->groupBy('category')->map(function ($group, $catName) use ($combinedTotal) {
            $total = round($group->sum('amount'), 2);
            $pct = $combinedTotal > 0 ? round(($total / $combinedTotal) * 100, 1) : 0;

            return [
                'name' => $catName,
                'count' => $group->count(),
                'total' => $total,
                'percentage' => $pct,
                'color' => $group->first()['category_color'] ?? '#64748b',
            ];
        })->sortByDesc('total')->values();

        $categories = ExpenseCategory::query()->orderBy('name')->get();
        $paymentMethods = $options->names('payment_method');

        return view('finance.all_expenses.index', compact(
            'allTransactions',
            'combinedTotal',
            'normalTotal',
            'personalTotal',
            'voluntaryTotal',
            'categoryBreakdown',
            'categories',
            'paymentMethods',
            'period',
            'fromDate',
            'toDate',
            'typeFilter'
        ));
    }

    private function resolveDates(string $periodType, ?string $start, ?string $end): array
    {
        $today = Carbon::today();

        return match ($periodType) {
            'day' => [$today->toDateString(), $today->toDateString()],
            'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'year' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
            'custom' => [
                $start ?: $today->copy()->startOfMonth()->toDateString(),
                $end ?: $today->toDateString(),
            ],
            default => [null, null],
        };
    }
}
