<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseClassificationController extends Controller
{
    public function index(Request $request, OptionsService $options): View
    {
        $user = $request->user();
        $classifications = $options->ensureClassifications($user->id);

        $domain = $request->get('domain', 'normal');
        $period = $request->get('period', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $categoryId = $request->get('category_id');
        $statusFilter = $request->get('status', 'all');

        [$startDate, $endDate] = $this->resolveDateRange($period, $fromDate, $toDate);

        $expenses = collect();
        $personalExpenses = collect();

        if (in_array($domain, ['normal', 'all'])) {
            $q = Expense::query()
                ->where('user_id', $user->id)
                ->whereNull('parent_id')
                ->with(['category'])
                ->orderByDesc('date');

            if ($startDate && $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            }
            if ($categoryId) {
                $q->where('category_id', $categoryId);
            }
            if ($statusFilter === 'unclassified') {
                $q->whereNull('classification');
            } elseif ($statusFilter !== 'all') {
                $q->where('classification', $statusFilter);
            }

            $expenses = $q->get();
        }

        if (in_array($domain, ['personal', 'all'])) {
            $q = PersonalExpense::query()
                ->where('user_id', $user->id)
                ->with(['category'])
                ->orderByDesc('date');

            if ($startDate && $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            }
            if ($categoryId) {
                $q->where('category_id', $categoryId);
            }
            if ($statusFilter === 'unclassified') {
                $q->whereNull('classification');
            } elseif ($statusFilter !== 'all') {
                $q->where('classification', $statusFilter);
            }

            $personalExpenses = $q->get();
        }

        $normalCategories = ExpenseCategory::orderBy('name')->get(['id', 'name']);
        $personalCategories = PersonalExpenseCategory::where('is_archived', false)->orderBy('name')->get(['id', 'name']);

        return view('finance.classification.index', compact(
            'classifications',
            'expenses',
            'personalExpenses',
            'domain',
            'period',
            'fromDate',
            'toDate',
            'categoryId',
            'statusFilter',
            'normalCategories',
            'personalCategories',
        ));
    }

    public function save(Request $request, OptionsService $options): RedirectResponse
    {
        $user = $request->user();
        $action = $request->get('action', 'save');
        $classificationNames = $options->names('expense_classification', $user->id);

        $items = $request->get('classifications', []);

        foreach ($items['expense'] ?? [] as $id => $classificationName) {
            $classificationName = ($classificationName === '' || $classificationName === null) ? null : $classificationName;
            if ($classificationName !== null && ! in_array($classificationName, $classificationNames)) {
                continue;
            }
            Expense::where('id', (int) $id)
                ->where('user_id', $user->id)
                ->update(['classification' => $classificationName]);
        }

        foreach ($items['personal_expense'] ?? [] as $id => $classificationName) {
            $classificationName = ($classificationName === '' || $classificationName === null) ? null : $classificationName;
            if ($classificationName !== null && ! in_array($classificationName, $classificationNames)) {
                continue;
            }
            PersonalExpense::where('id', (int) $id)
                ->where('user_id', $user->id)
                ->update(['classification' => $classificationName]);
        }

        if ($action === 'save_and_pdf') {
            return redirect()->route('classification.print', array_filter([
                'domain' => $request->get('domain'),
                'period' => $request->get('period'),
                'from_date' => $request->get('from_date'),
                'to_date' => $request->get('to_date'),
                'category_id' => $request->get('category_id'),
            ]))->with('success', 'Classifications saved.');
        }

        return back()->with('success', 'Classifications saved successfully.');
    }

    public function printReport(Request $request, OptionsService $options): View
    {
        $user = $request->user();
        $classifications = $options->ensureClassifications($user->id);

        $domain = $request->get('domain', 'normal');
        $period = $request->get('period', 'month');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $categoryId = $request->get('category_id');

        [$startDate, $endDate] = $this->resolveDateRange($period, $fromDate, $toDate);

        $expenses = collect();
        $personalExpenses = collect();

        if (in_array($domain, ['normal', 'all'])) {
            $q = Expense::query()
                ->where('user_id', $user->id)
                ->whereNull('parent_id')
                ->with(['category'])
                ->orderByDesc('date');

            if ($startDate && $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            }
            if ($categoryId) {
                $q->where('category_id', $categoryId);
            }

            $expenses = $q->get();
        }

        if (in_array($domain, ['personal', 'all'])) {
            $q = PersonalExpense::query()
                ->where('user_id', $user->id)
                ->with(['category'])
                ->orderByDesc('date');

            if ($startDate && $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            }
            if ($categoryId) {
                $q->where('category_id', $categoryId);
            }

            $personalExpenses = $q->get();
        }

        $allItems = $expenses->map(fn ($e) => [
            'type' => 'normal',
            'date' => $e->date,
            'description' => $e->description,
            'category' => $e->category?->name ?? '—',
            'payment_method' => $e->payment_method ?? '—',
            'amount' => (float) $e->totalAmount(),
            'classification' => $e->classification ?? 'Unclassified',
        ])->merge(
            $personalExpenses->map(fn ($p) => [
                'type' => 'personal',
                'date' => $p->date,
                'description' => $p->description,
                'category' => $p->category?->name ?? '—',
                'payment_method' => $p->payment_method ?? '—',
                'amount' => (float) $p->totalAmount(),
                'classification' => $p->classification ?? 'Unclassified',
            ])
        )->sortByDesc('date')->values();

        $byClassification = $allItems->groupBy('classification')->map(fn ($g) => [
            'count' => $g->count(),
            'amount' => $g->sum('amount'),
        ]);

        $byClassificationCategory = $allItems->groupBy(fn ($i) => $i['classification'].'||'.$i['category'])
            ->map(fn ($g, $key) => [
                'classification' => explode('||', $key)[0],
                'category' => explode('||', $key)[1],
                'count' => $g->count(),
                'amount' => $g->sum('amount'),
            ])->values()->sortBy('classification');

        $byClassificationCategoryPayment = $allItems->groupBy(fn ($i) => $i['classification'].'||'.$i['category'].'||'.$i['payment_method'])
            ->map(fn ($g, $key) => [
                'classification' => explode('||', $key)[0],
                'category' => explode('||', $key)[1],
                'payment_method' => explode('||', $key)[2],
                'count' => $g->count(),
                'amount' => $g->sum('amount'),
            ])->values()->sortBy('classification');

        $totalAmount = $allItems->sum('amount');
        $periodLabel = $this->buildPeriodLabel($period, $startDate, $endDate);

        return view('finance.classification.print', compact(
            'allItems',
            'byClassification',
            'byClassificationCategory',
            'byClassificationCategoryPayment',
            'totalAmount',
            'periodLabel',
            'domain',
            'classifications',
        ));
    }

    /** @return array{0: ?string, 1: ?string} */
    private function resolveDateRange(string $period, ?string $fromDate, ?string $toDate): array
    {
        $now = Carbon::now();

        return match ($period) {
            'day' => [$now->toDateString(), $now->toDateString()],
            'week' => [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
            'month' => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
            'year' => [$now->copy()->startOfYear()->toDateString(), $now->copy()->endOfYear()->toDateString()],
            'custom' => [$fromDate, $toDate],
            default => [null, null],
        };
    }

    private function buildPeriodLabel(string $period, ?string $startDate, ?string $endDate): string
    {
        $now = Carbon::now();

        return match ($period) {
            'day' => 'Today – '.$now->format('d M Y'),
            'week' => 'This Week – '.$now->copy()->startOfWeek()->format('d M').' to '.$now->copy()->endOfWeek()->format('d M Y'),
            'month' => 'This Month – '.$now->format('F Y'),
            'year' => 'This Year – '.$now->format('Y'),
            'custom' => ($startDate ? Carbon::parse($startDate)->format('d M Y') : '?').' to '.($endDate ? Carbon::parse($endDate)->format('d M Y') : '?'),
            default => 'All Time',
        };
    }
}
