<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseStatement;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatementController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tab = $request->get('tab', 'normal');

        $normalStatements = ExpenseStatement::query()
            ->where('user_id', $user->id)
            ->where('type', 'normal')
            ->latest()
            ->get();

        $personalStatements = ExpenseStatement::query()
            ->where('user_id', $user->id)
            ->where('type', 'personal')
            ->latest()
            ->get();

        $normalCategories = ExpenseCategory::query()->orderBy('name')->get();
        $personalCategories = PersonalExpenseCategory::query()->where('is_archived', false)->orderBy('name')->get();

        // Sample expenses for custom picker dialogs
        $recentNormalExpenses = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->with('category')
            ->latest('date')
            ->take(100)
            ->get();

        $recentPersonalExpenses = PersonalExpense::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->latest('date')
            ->take(100)
            ->get();

        return view('finance.statements.index', compact(
            'tab',
            'normalStatements',
            'personalStatements',
            'normalCategories',
            'personalCategories',
            'recentNormalExpenses',
            'recentPersonalExpenses'
        ));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $type = $request->get('type', 'normal');

        $normalCategories = ExpenseCategory::query()->orderBy('name')->get();
        $personalCategories = PersonalExpenseCategory::query()->where('is_archived', false)->orderBy('name')->get();

        $recentNormalExpenses = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->with('category')
            ->latest('date')
            ->take(100)
            ->get();

        $recentPersonalExpenses = PersonalExpense::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->latest('date')
            ->take(100)
            ->get();

        return view('finance.statements.create', compact(
            'type',
            'normalCategories',
            'personalCategories',
            'recentNormalExpenses',
            'recentPersonalExpenses'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:normal,personal',
            'period_type' => 'required|in:all,day,week,month,year,custom',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer',
            'custom_expense_ids' => 'nullable|array',
            'custom_expense_ids.*' => 'integer',
            'notes' => 'nullable|string',
        ]);

        [$startDate, $endDate] = $this->resolveDates($validated['period_type'], $validated['start_date'] ?? null, $validated['end_date'] ?? null);

        $totalAmount = 0.0;
        $type = $validated['type'];

        if (! empty($validated['custom_expense_ids'])) {
            $ids = array_map('intval', $validated['custom_expense_ids']);
            if ($type === 'normal') {
                $totalAmount = (float) Expense::query()
                    ->where('user_id', $user->id)
                    ->whereIn('id', $ids)
                    ->sum(DB::raw('amount + gst_amount'));
            } else {
                $totalAmount = (float) PersonalExpense::query()
                    ->where('user_id', $user->id)
                    ->whereIn('id', $ids)
                    ->sum('amount');
            }
        } else {
            if ($type === 'normal') {
                $q = Expense::query()->where('user_id', $user->id)->whereNull('parent_id');
                if ($startDate && $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                }
                if (! empty($validated['category_ids'])) {
                    $q->whereIn('category_id', $validated['category_ids']);
                }
                $totalAmount = (float) $q->sum(DB::raw('amount + gst_amount'));
            } else {
                $q = PersonalExpense::query()->where('user_id', $user->id);
                if ($startDate && $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                }
                if (! empty($validated['category_ids'])) {
                    $q->whereIn('category_id', $validated['category_ids']);
                }
                $totalAmount = (float) $q->sum('amount');
            }
        }

        $count = ExpenseStatement::where('user_id', $user->id)->where('type', $type)->count() + 1;
        $title = ! empty($validated['title'])
            ? $validated['title']
            : ucfirst($type).' Expense Statement #'.$count.' ('.now()->format('M Y').')';

        $statement = ExpenseStatement::create([
            'user_id' => $user->id,
            'title' => $title,
            'type' => $type,
            'period_type' => $validated['period_type'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'category_ids' => $validated['category_ids'] ?? null,
            'custom_expense_ids' => $validated['custom_expense_ids'] ?? null,
            'total_amount' => $totalAmount,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('statements.show', $statement)->with('success', 'Statement created successfully!');
    }

    public function show(Request $request, ExpenseStatement $statement): View
    {
        abort_if($statement->user_id !== auth()->id(), 403);

        $items = collect();
        $user = $request->user();

        if (! empty($statement->custom_expense_ids)) {
            $ids = array_map('intval', $statement->custom_expense_ids);
            if ($statement->isNormal()) {
                $items = Expense::query()
                    ->where('user_id', $user->id)
                    ->whereIn('id', $ids)
                    ->with('category')
                    ->orderByDesc('date')
                    ->get();
            } else {
                $items = PersonalExpense::query()
                    ->where('user_id', $user->id)
                    ->whereIn('id', $ids)
                    ->with('category')
                    ->orderByDesc('date')
                    ->get();
            }
        } else {
            if ($statement->isNormal()) {
                $q = Expense::query()
                    ->where('user_id', $user->id)
                    ->whereNull('parent_id')
                    ->with('category');
                if ($statement->start_date && $statement->end_date) {
                    $q->whereBetween('date', [$statement->start_date->toDateString(), $statement->end_date->toDateString()]);
                }
                if (! empty($statement->category_ids)) {
                    $q->whereIn('category_id', $statement->category_ids);
                }
                $items = $q->orderByDesc('date')->get();
            } else {
                $q = PersonalExpense::query()
                    ->where('user_id', $user->id)
                    ->with('category');
                if ($statement->start_date && $statement->end_date) {
                    $q->whereBetween('date', [$statement->start_date->toDateString(), $statement->end_date->toDateString()]);
                }
                if (! empty($statement->category_ids)) {
                    $q->whereIn('category_id', $statement->category_ids);
                }
                $items = $q->orderByDesc('date')->get();
            }
        }

        // Category breakdown calculation
        $categoryBreakdown = $items->groupBy(fn ($item) => $item->category?->name ?? 'Uncategorized')
            ->map(function ($group) use ($statement) {
                $sum = $group->sum(fn ($i) => $statement->isNormal() ? $i->totalAmount() : (float) $i->amount);

                return [
                    'count' => $group->count(),
                    'total' => round($sum, 2),
                ];
            });

        return view('finance.statements.show', compact('statement', 'items', 'categoryBreakdown'));
    }

    public function update(Request $request, ExpenseStatement $statement): RedirectResponse
    {
        abort_if($statement->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $statement->update($validated);

        return back()->with('success', 'Statement updated successfully.');
    }

    public function destroy(ExpenseStatement $statement): RedirectResponse
    {
        abort_if($statement->user_id !== auth()->id(), 403);

        $type = $statement->type;
        $statement->delete();

        return redirect()->route('statements.index', ['tab' => $type])->with('success', 'Statement deleted.');
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
