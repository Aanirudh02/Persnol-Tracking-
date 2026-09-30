<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\IncomeExpenseTally;
use App\Services\AuditService;
use App\Services\FinanceService;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Income::where('user_id', $user->id)->with('category');

        if ($request->filled('source')) {
            $query->where('source', 'like', "%{$request->source}%");
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        $incomes = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = IncomeCategory::all();
        $totalAmount = (clone $query)->sum('amount');

        return view('finance.income.index', compact('incomes', 'categories', 'totalAmount'));
    }

    public function create(OptionsService $options)
    {
        $categories = IncomeCategory::all();
        $paymentMethods = $options->names('payment_method');
        $incomeSources = $options->names('income_source');

        return view('finance.income.create', compact('categories', 'paymentMethods', 'incomeSources'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|exists:income_categories,id',
            'source' => 'required|string|max:100',
            'date' => 'required|date',
            'time' => 'nullable',
            'payment_method' => 'required|string',
            'tally_mode' => 'nullable|in:tally_current,tally_future,separate',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $dailyRecord = DailyRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $validated['date'],
        ]);

        $income = Income::create([
            'user_id' => $request->user()->id,
            'daily_record_id' => $dailyRecord->id,
            'category_id' => $validated['category_id'] ?? null,
            'amount' => $validated['amount'],
            'source' => $validated['source'],
            'date' => $validated['date'],
            'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
            'payment_method' => $validated['payment_method'],
            'tally_mode' => $validated['tally_mode'] ?? 'separate',
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditService::log('income', $income->id, 'created', null, $income->toArray(), 'Income created');

        return redirect()->route('income.index')->with('success', 'Income recorded successfully!');
    }

    public function edit(Income $income, FinanceService $financeService, OptionsService $options)
    {
        if ($income->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('income', $income)) {
            return redirect()->route('income.index')->with('error', '🔒 This income record is locked.');
        }

        $categories = IncomeCategory::all();
        $paymentMethods = $options->names('payment_method');
        $incomeSources = $options->names('income_source');

        return view('finance.income.edit', compact('income', 'categories', 'paymentMethods', 'incomeSources'));
    }

    public function update(Request $request, Income $income, FinanceService $financeService)
    {
        if ($income->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('income', $income)) {
            return redirect()->route('income.index')->with('error', '🔒 This income record is locked.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|exists:income_categories,id',
            'source' => 'required|string|max:100',
            'date' => 'required|date',
            'time' => 'nullable',
            'payment_method' => 'required|string',
            'tally_mode' => 'nullable|in:tally_current,tally_future,separate',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldValues = $income->only(['amount', 'source', 'date', 'payment_method', 'tally_mode']);
        $income->update([
            'amount' => $validated['amount'],
            'category_id' => $validated['category_id'] ?? null,
            'source' => $validated['source'],
            'date' => $validated['date'],
            'time' => $validated['time'] ?? $income->time,
            'payment_method' => $validated['payment_method'],
            'tally_mode' => $validated['tally_mode'] ?? 'separate',
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditService::log(
            module: 'income',
            recordId: $income->id,
            action: 'updated',
            oldValues: $oldValues,
            newValues: $income->only(['amount', 'source', 'date', 'payment_method', 'tally_mode']),
            reason: $request->input('reason', 'Updated by user')
        );

        return redirect()->route('income.index')->with('success', 'Income record updated!');
    }

    public function show(Request $request, Income $income): View
    {
        if ($income->user_id !== $request->user()->id) {
            abort(403);
        }

        $income->load(['category', 'tallies.expense.category']);

        $allExpenses = Expense::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->with(['incomeTallies.income'])
            ->orderByDesc('date')
            ->take(60)
            ->get();

        $allExpenses->each(function (Expense $exp) use ($income): void {
            $totalTallied = (float) $exp->incomeTallies->sum('allocated_amount');
            $thisTally = $exp->incomeTallies->firstWhere('income_id', $income->id);
            $otherTallies = $exp->incomeTallies->where('income_id', '!=', $income->id);
            $untalliedExpAmount = max(0, $exp->totalAmount() - $totalTallied);

            $exp->total_tallied_amount = $totalTallied;
            $exp->available_to_tally = $untalliedExpAmount;

            if ($thisTally) {
                $exp->is_selectable = false;
                $exp->tally_badge = 'Already tallied in this income (₹'.number_format((float) $thisTally->allocated_amount, 2).')';
            } elseif ($totalTallied >= $exp->totalAmount() && $exp->totalAmount() > 0) {
                $exp->is_selectable = false;
                $otherSource = $otherTallies->first()?->income?->source ?? 'another income';
                $exp->tally_badge = 'Fully tallied under '.$otherSource.' (₹'.number_format($totalTallied, 2).')';
            } elseif ($totalTallied > 0) {
                $exp->is_selectable = true;
                $exp->tally_badge = 'Partially tallied (₹'.number_format($totalTallied, 2).' / ₹'.number_format($exp->totalAmount(), 2).')';
            } else {
                $exp->is_selectable = true;
                $exp->tally_badge = 'Untallied';
            }
        });

        $talliedTotal = $income->talliedAmount();
        $untalliedTotal = $income->untalliedAmount();

        return view('finance.income.show', [
            'income' => $income,
            'availableExpenses' => $allExpenses,
            'talliedTotal' => $talliedTotal,
            'untalliedTotal' => $untalliedTotal,
        ]);
    }

    public function tallyExpense(Request $request, Income $income): RedirectResponse
    {
        if ($income->user_id !== $request->user()->id) {
            abort(403);
        }

        $untallied = $income->untalliedAmount();
        $validated = $request->validate([
            'expense_id' => ['required', 'exists:expenses,id'],
            'allocated_amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, $untallied + 0.01)],
            'notes' => ['nullable', 'string'],
        ]);

        $expense = Expense::query()->where('user_id', $request->user()->id)->with(['incomeTallies'])->findOrFail($validated['expense_id']);

        if (IncomeExpenseTally::where('income_id', $income->id)->where('expense_id', $expense->id)->exists()) {
            return back()->with('error', 'This expense is already tallied under this income stream.');
        }

        $expUntallied = $expense->untalliedAmount();
        if ((float) $validated['allocated_amount'] > $expUntallied + 0.01) {
            return back()->with('error', 'The selected expense only has ₹'.number_format($expUntallied, 2).' available to tally.');
        }

        IncomeExpenseTally::create([
            'user_id' => $request->user()->id,
            'income_id' => $income->id,
            'expense_id' => $expense->id,
            'allocated_amount' => $validated['allocated_amount'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Expense tallied against income for ₹'.number_format((float) $validated['allocated_amount'], 2).'.');
    }

    public function untallyExpense(Request $request, Income $income, IncomeExpenseTally $tally): RedirectResponse
    {
        if ($income->user_id !== $request->user()->id || $tally->income_id !== $income->id) {
            abort(403);
        }

        $tally->delete();

        return back()->with('success', 'Expense tally removed.');
    }

    public function destroy(Request $request, Income $income): RedirectResponse
    {
        if ($income->user_id !== auth()->id()) {
            abort(403);
        }

        AuditService::log('income', $income->id, 'deleted', $income->toArray(), null, $request->input('reason', 'Deleted by user'));
        $income->delete();

        return redirect()->route('income.index')->with('success', 'Income record deleted.');
    }
}
