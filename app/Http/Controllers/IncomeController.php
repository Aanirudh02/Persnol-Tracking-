<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\IncomeExpenseTally;
use App\Services\AuditService;
use App\Services\CreditDebtLinkService;
use App\Services\FinanceService;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        if ($request->filled('month')) {
            $m = Carbon::parse($request->get('month').'-01');
            $query->whereBetween('date', [$m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()]);
        }

        $month = $request->get('month');
        $incomes = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = IncomeCategory::all();
        $totalAmount = (clone $query)->sum('amount');

        return view('finance.income.index', compact('incomes', 'categories', 'totalAmount', 'month'));
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

    public function edit(Income $income, FinanceService $financeService, OptionsService $options, CreditDebtLinkService $creditLinkService)
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
        $creditLinked = $creditLinkService->describeCreditFor($income);

        return view('finance.income.edit', compact('income', 'categories', 'paymentMethods', 'incomeSources', 'creditLinked'));
    }

    public function update(Request $request, Income $income, FinanceService $financeService, CreditDebtLinkService $creditLinkService)
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
        $valuesBeforeEdit = [
            'payment_method' => $income->payment_method,
            'date' => $income->date?->toDateString(),
            'amount' => (float) $income->amount,
        ];
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

        // Only when the user confirmed "update both places" in the prompt
        $syncSummary = $creditLinkService->syncCreditFromRecord(
            $income->fresh(),
            $valuesBeforeEdit,
            $creditLinkService->requestedFields($request->input('sync_linked', []))
        );

        return redirect()->route('income.index')->with('success', trim('Income record updated! '.implode(' ', $syncSummary)));
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
            ->where('is_voluntary', false)
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

        $availableList = $allExpenses->where('is_selectable', true)->values();
        $talliedList = $allExpenses->where('is_selectable', false)->values();

        return view('finance.income.show', [
            'income' => $income,
            'availableExpenses' => $availableList,
            'talliedExpenses' => $talliedList,
            'talliedTotal' => $talliedTotal,
            'untalliedTotal' => $untalliedTotal,
        ]);
    }

    public function tallyExpense(Request $request, Income $income): RedirectResponse
    {
        if ($income->user_id !== $request->user()->id) {
            abort(403);
        }

        $isBulk = $request->has('expense_ids');
        $validated = $isBulk
            ? $request->validate([
                'expense_ids' => ['required', 'array', 'min:1'],
                'expense_ids.*' => ['required', 'integer', 'distinct', 'exists:expenses,id'],
                'notes' => ['nullable', 'string'],
            ])
            : $request->validate([
                'expense_id' => ['required', 'exists:expenses,id'],
                'allocated_amount' => ['required', 'numeric', 'min:0.01'],
                'notes' => ['nullable', 'string'],
            ]);

        $expenseIds = $isBulk
            ? array_map('intval', $validated['expense_ids'])
            : [(int) $validated['expense_id']];

        $result = DB::transaction(function () use ($request, $income, $expenseIds, $isBulk, $validated): array {
            $lockedIncome = Income::query()
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->findOrFail($income->id);
            $expenses = Expense::query()
                ->where('user_id', $request->user()->id)
                ->whereNull('parent_id')
                ->where('is_voluntary', false)
                ->where(fn ($query) => $query->whereNull('is_archived')->orWhere('is_archived', false))
                ->whereIn('id', $expenseIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($expenses->count() !== count(array_unique($expenseIds))) {
                throw ValidationException::withMessages([
                    'expense_ids' => 'Choose only your own active, non-voluntary expenses.',
                ]);
            }

            $allocations = [];
            $totalToTally = 0.0;
            foreach ($expenses as $expense) {
                if (IncomeExpenseTally::query()
                    ->where('income_id', $lockedIncome->id)
                    ->where('expense_id', $expense->id)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'expense_ids' => 'An expense is already tallied under this income stream.',
                    ]);
                }

                $availableAmount = $expense->untalliedAmount();
                $allocatedAmount = $isBulk ? $availableAmount : (float) $validated['allocated_amount'];

                if ($allocatedAmount <= 0 || $allocatedAmount > $availableAmount + 0.01) {
                    throw ValidationException::withMessages([
                        ($isBulk ? 'expense_ids' : 'allocated_amount') => 'A selected expense does not have enough untallied amount available.',
                    ]);
                }

                $allocations[] = [$expense, round($allocatedAmount, 2)];
                $totalToTally += $allocatedAmount;
            }

            if ($totalToTally > $lockedIncome->untalliedAmount() + 0.01) {
                throw ValidationException::withMessages([
                    ($isBulk ? 'expense_ids' : 'allocated_amount') => 'The selected amount is greater than the income amount still available to tally.',
                ]);
            }

            foreach ($allocations as [$expense, $allocatedAmount]) {
                IncomeExpenseTally::query()->create([
                    'user_id' => $request->user()->id,
                    'income_id' => $lockedIncome->id,
                    'expense_id' => $expense->id,
                    'allocated_amount' => $allocatedAmount,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            return ['count' => count($allocations), 'amount' => round($totalToTally, 2)];
        });

        return back()->with(
            'success',
            $isBulk
                ? $result['count'].' expenses tallied against income for ₹'.number_format($result['amount'], 2).'.'
                : 'Expense tallied against income for ₹'.number_format($result['amount'], 2).'.'
        );
    }

    public function untallyExpense(Request $request, Income $income, IncomeExpenseTally $tally): RedirectResponse
    {
        if ($income->user_id !== $request->user()->id || $tally->income_id !== $income->id) {
            abort(403);
        }

        $tally->delete();

        return back()->with('success', 'Expense tally removed.');
    }

    public function destroy(Request $request, Income $income, FinanceService $financeService): RedirectResponse
    {
        if ($income->user_id !== auth()->id()) {
            abort(403);
        }

        // Same rule as editing: locked / out-of-window records can't be removed either
        if (! $financeService->canEdit('income', $income)) {
            return redirect()->route('income.index')->with('error', '🔒 This income record is locked and cannot be deleted.');
        }

        DB::transaction(function () use ($request, $income): void {
            AuditService::log('income', $income->id, 'deleted', $income->toArray(), null, $request->input('reason', 'Deleted by user'));
            // Its tallies against expenses go with it, so expenses don't stay "tallied" by a deleted income
            $income->tallies()->delete();
            $income->delete();
        });

        return redirect()->route('income.index')->with('success', 'Income record deleted.');
    }
}
