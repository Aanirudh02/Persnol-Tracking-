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
use App\Services\MonthFilter;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
            'keep_separate' => 'nullable|boolean',
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
            'keep_separate' => $request->boolean('keep_separate'),
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
            'keep_separate' => 'nullable|boolean',
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
            'keep_separate' => $request->boolean('keep_separate'),
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

        $income->load([
            'category',
            'tallies.expense.category',
            'tallies.expense.incomeTallies.income',
            'tallies.expense.friendSplit.friend',
            'tallies.expense.friendSplits.friend',
        ]);

        $monthFilter = MonthFilter::fromRequest($request, $income->date);
        $showFullyTallied = $request->boolean('show_tallied');
        $rawSelectedIds = $request->query('selected');
        $selectedExpenseIds = collect(is_string($rawSelectedIds) ? explode(',', $rawSelectedIds) : [])
            ->merge((array) $request->old('expense_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $monthExpenses = $this->tallyCandidatesQuery($request, $income)
            ->whereBetween('date', $monthFilter->dateRange())
            ->with(['category', 'expenseGroup', 'incomeTallies.income', 'friendSplit.friend', 'friendSplits.friend'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->each(fn (Expense $expense) => $this->annotateTallyState($expense));

        $fullyTalliedCount = $monthExpenses->where('is_selectable', false)->count();
        $expenseOptions = $showFullyTallied
            ? $monthExpenses
            : $monthExpenses->where('is_selectable', true)->values();

        $selectedInOtherMonths = $selectedExpenseIds->isEmpty()
            ? collect()
            : $this->tallyCandidatesQuery($request, $income)
                ->whereIn('id', $selectedExpenseIds)
                ->whereNotBetween('date', $monthFilter->dateRange())
                ->with(['category', 'incomeTallies.income'])
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->each(fn (Expense $expense) => $this->annotateTallyState($expense))
                ->where('is_selectable', true)
                ->values();

        $unpaidInOtherMonths = $this->tallyCandidatesQuery($request, $income)
            ->whereNotBetween('date', $monthFilter->dateRange())
            ->select(['id', 'date', 'amount', 'gst_amount'])
            ->withSum('incomeTallies as tallied_sum', 'allocated_amount')
            ->get()
            ->filter(fn (Expense $expense): bool => round($expense->totalAmount() - (float) $expense->tallied_sum, 2) > 0)
            ->groupBy(fn (Expense $expense): string => $expense->date->format('Y-m'))
            ->sortKeys()
            ->map(fn ($expenses, string $month): array => [
                'month' => $month,
                'label' => MonthFilter::parse($month)?->format('M Y') ?? $month,
                'count' => $expenses->count(),
            ])
            ->values();

        return view('finance.income.show', [
            'income' => $income,
            'expenses' => $expenseOptions,
            'monthFilter' => $monthFilter,
            'showFullyTallied' => $showFullyTallied,
            'fullyTalliedCount' => $fullyTalliedCount,
            'selectedExpenseIds' => $selectedExpenseIds->all(),
            'selectedInOtherMonths' => $selectedInOtherMonths,
            'unpaidInOtherMonths' => $unpaidInOtherMonths,
            'talliedTotal' => $income->talliedAmount(),
            'untalliedTotal' => $income->untalliedAmount(),
        ]);
    }

    /**
     * Expenses this income may be tallied against, excluding ones it already covers.
     *
     * @return Builder<Expense>
     */
    private function tallyCandidatesQuery(Request $request, Income $income): Builder
    {
        return Expense::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->where('is_voluntary', false)
            ->where('payment_method', $income->payment_method)
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereDoesntHave('incomeTallies', fn ($q) => $q->where('income_id', $income->id));
    }

    /**
     * Sets the tally fields the tally page reads: total_tallied_amount,
     * available_to_tally, is_selectable and tally_badge.
     */
    private function annotateTallyState(Expense $expense): void
    {
        $totalTallied = (float) $expense->incomeTallies->sum('allocated_amount');

        $expense->total_tallied_amount = $totalTallied;
        $expense->available_to_tally = round(max(0, $expense->totalAmount() - $totalTallied), 2);

        if ($totalTallied >= $expense->totalAmount() && $expense->totalAmount() > 0) {
            $expense->is_selectable = false;
            $otherSource = $expense->incomeTallies->first()?->income?->source ?? 'another income';
            $expense->tally_badge = 'Fully tallied under '.$otherSource.' (₹'.number_format($totalTallied, 2).')';
        } elseif ($totalTallied > 0) {
            $expense->is_selectable = true;
            $expense->tally_badge = 'Partially tallied';
        } else {
            $expense->is_selectable = true;
            $expense->tally_badge = 'Untallied';
        }
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
                'confirm_partial' => ['nullable', 'boolean'],
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
                ->where('payment_method', $lockedIncome->payment_method)
                ->where(fn ($query) => $query->whereNull('is_archived')->orWhere('is_archived', false))
                ->whereIn('id', $expenseIds)
                ->with('incomeTallies')
                ->orderBy('date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($expenses->count() !== count(array_unique($expenseIds))) {
                throw ValidationException::withMessages([
                    ($isBulk ? 'expense_ids' : 'expense_id') => 'Choose only your own active, non-voluntary expenses that match this income’s payment method.',
                ]);
            }

            $incomeAvailable = round($lockedIncome->untalliedAmount(), 2);
            $allowPartial = $isBulk && $request->boolean('confirm_partial');
            $incomeLeft = $incomeAvailable;
            $allocations = [];
            $totalToTally = 0.0;
            foreach ($expenses as $expense) {
                if ($expense->incomeTallies->contains('income_id', $lockedIncome->id)) {
                    throw ValidationException::withMessages([
                        ($isBulk ? 'expense_ids' : 'expense_id') => 'This income is already tallied against a selected expense.',
                    ]);
                }

                $availableAmount = round($expense->untalliedAmount(), 2);
                $allocatedAmount = $isBulk ? $availableAmount : (float) $validated['allocated_amount'];

                // Oldest expenses are covered first; the one that exceeds the income gets what is left.
                if ($allowPartial && $availableAmount > 0) {
                    if ($incomeLeft <= 0) {
                        throw ValidationException::withMessages([
                            'expense_ids' => 'The income runs out before every selected expense is reached. Select fewer expenses.',
                        ]);
                    }

                    $allocatedAmount = min($availableAmount, $incomeLeft);
                    $incomeLeft = round($incomeLeft - $allocatedAmount, 2);
                }

                if ($allocatedAmount <= 0 || $allocatedAmount > $availableAmount + 0.01) {
                    throw ValidationException::withMessages([
                        ($isBulk ? 'expense_ids' : 'allocated_amount') => 'A selected expense does not have enough untallied amount available.',
                    ]);
                }

                $allocations[] = [$expense, round($allocatedAmount, 2)];
                $totalToTally += $allocatedAmount;
            }

            if ($totalToTally > $incomeAvailable + 0.01) {
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

        if (! $isBulk) {
            return back()->with('success', 'Expense tallied against income for ₹'.number_format($result['amount'], 2).'.');
        }

        // Return to the month being viewed, without the now-tallied selection.
        $returnMonth = MonthFilter::parse((string) $request->input('month', ''))?->format('Y-m');

        return redirect()
            ->route('income.show', array_filter([
                'income' => $income,
                'month' => $returnMonth,
                'show_tallied' => $request->boolean('show_tallied') ? 1 : null,
            ]))
            ->with('success', $result['count'].' expenses tallied against income for ₹'.number_format($result['amount'], 2).'.');
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
