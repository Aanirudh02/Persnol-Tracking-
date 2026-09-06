<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseGroup;
use App\Models\FoodEntry;
use App\Models\Friend;
use App\Services\AuditService;
use App\Services\FinanceLinkService;
use App\Services\FinanceService;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->with(['category', 'subItems', 'paidByFriend', 'expenseGroup.expenses.category']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }
        if ($request->boolean('voluntary_only')) {
            $query->where('is_voluntary', true);
        }

        $expenses = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = ExpenseCategory::orderBy('name')->get();
        $paymentMethods = app(OptionsService::class)->names('payment_method');

        $totalQuery = Expense::where('expenses.user_id', $user->id)
            ->whereNull('expenses.parent_id')
            ->where('expenses.is_voluntary', false)
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->where(function ($q) {
                $q->whereNull('expense_categories.is_archived')
                    ->orWhere('expense_categories.is_archived', false);
            });
        if ($request->filled('from_date')) {
            $totalQuery->where('expenses.date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $totalQuery->where('expenses.date', '<=', $request->to_date);
        }
        $totalAmount = (float) $totalQuery->sum(DB::raw('expenses.amount + expenses.gst_amount'));

        return view('finance.expenses.index', compact('expenses', 'categories', 'totalAmount', 'paymentMethods'));
    }

    public function create(OptionsService $options)
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        $friends = Friend::where('user_id', auth()->id())->orderBy('name')->get();
        $paymentMethods = $options->names('payment_method');
        $parents = Expense::where('user_id', auth()->id())->whereNull('parent_id')->latest()->take(30)->get();

        return view('finance.expenses.create', compact('categories', 'friends', 'paymentMethods', 'parents'));
    }

    public function store(Request $request, FinanceLinkService $linkService)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'gst_amount' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:expense_categories,id',
            'parent_id' => 'nullable|exists:expenses,id',
            'date' => 'required|date',
            'time' => 'nullable',
            'description' => 'required|string|max:255',
            'payment_method' => 'required|string',
            'paid_by_type' => 'nullable|in:me,friend',
            'paid_by_friend_id' => 'nullable|exists:friends,id',
            'split_with_friend_id' => 'nullable|exists:friends,id',
            'split_my_share' => 'nullable|numeric|min:0',
            'split_friend_share' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_voluntary' => 'nullable|boolean',
            'add_group_expense' => 'nullable|boolean',
            'group_expenses' => 'nullable|array|min:2',
            'group_expenses.*.amount' => 'required_with:add_group_expense|numeric|min:0.01',
            'group_expenses.*.payment_method' => 'required_with:add_group_expense|string|max:50',
        ]);

        $isGrouped = $request->boolean('add_group_expense') && empty($validated['parent_id']);
        $gstAmount = round((float) ($validated['gst_amount'] ?? 0), 2);
        $groupLines = collect($validated['group_expenses'] ?? [])
            ->filter(fn (array $line): bool => filled($line['amount'] ?? null) && filled($line['payment_method'] ?? null))
            ->values();

        if ($isGrouped) {
            if ($groupLines->count() < 2) {
                return back()->withInput()->withErrors(['group_expenses' => 'Add at least two payment lines for a grouped expense.']);
            }

            $groupTotal = round((float) $groupLines->sum(fn (array $line): float => (float) $line['amount']), 2);
            if (abs($groupTotal - (float) $validated['amount']) > 0.01) {
                return back()->withInput()->withErrors(['group_expenses' => 'Payment lines must add up to the total expense amount.']);
            }
        }

        $imagePath = null;
        if ($request->hasFile('receipt_image')) {
            $imagePath = $request->file('receipt_image')->store('receipts', 'public');
        }

        $paidByType = $validated['paid_by_type'] ?? 'me';
        $paidByLabel = 'Me';
        if ($paidByType === 'friend' && ! empty($validated['paid_by_friend_id'])) {
            $friend = Friend::find($validated['paid_by_friend_id']);
            $paidByLabel = $friend?->name ?? 'Friend';
        }

        $parentId = $validated['parent_id'] ?? null;
        $categoryId = $validated['category_id'] ?? null;
        if ($parentId) {
            $parent = Expense::where('user_id', $request->user()->id)->findOrFail($parentId);
            $categoryId = $parent->category_id;
            $validated['payment_method'] = $validated['payment_method'] ?: $parent->payment_method;
            $validated['date'] = $parent->date->toDateString();

            if ((float) $validated['amount'] + $gstAmount > $parent->remainingAmount()) {
                return back()->withInput()->withErrors(['amount' => 'This sub-item exceeds the remaining amount of the parent expense.']);
            }
        }

        $category = $categoryId ? ExpenseCategory::find($categoryId) : null;
        $isVoluntary = $request->boolean('is_voluntary') || ($category?->is_voluntary ?? false);

        $expenses = DB::transaction(function () use ($request, $validated, $linkService, $imagePath, $paidByType, $paidByLabel, $parentId, $categoryId, $isVoluntary, $groupLines, $gstAmount, $isGrouped) {
            $dailyRecord = DailyRecord::firstOrCreate([
                'user_id' => $request->user()->id,
                'record_date' => $validated['date'],
            ]);

            $group = $isGrouped
                ? ExpenseGroup::create([
                    'user_id' => $request->user()->id,
                    'name' => $validated['description'],
                ])
                : null;

            $lines = $isGrouped
                ? $groupLines
                : collect([[
                    'amount' => $validated['amount'],
                    'payment_method' => $validated['payment_method'],
                ]]);

            $baseTotal = (float) $lines->sum(fn (array $line): float => (float) $line['amount']);
            $allocatedGst = $lines->values()->map(function (array $line, int $index) use ($gstAmount, $baseTotal, $lines): array {
                $gst = $index === $lines->count() - 1
                    ? $gstAmount - (float) $lines->slice(0, $index)->sum('gst_amount')
                    : ($baseTotal > 0 ? round($gstAmount * ((float) $line['amount'] / $baseTotal), 2) : 0);

                $line['gst_amount'] = round($gst, 2);

                return $line;
            });

            if ($allocatedGst->isNotEmpty()) {
                $allocatedGst = $allocatedGst->values();
                $allocatedGst = $allocatedGst->map(function (array $line, int $index) use ($gstAmount, $allocatedGst): array {
                    if ($index === $allocatedGst->count() - 1) {
                        $line['gst_amount'] = round($gstAmount - (float) $allocatedGst->slice(0, $index)->sum('gst_amount'), 2);
                    }

                    return $line;
                });
            }

            return $allocatedGst->map(function (array $line) use ($request, $validated, $linkService, $imagePath, $paidByType, $paidByLabel, $parentId, $categoryId, $isVoluntary, $dailyRecord, $group): Expense {
                $expense = Expense::create([
                    'user_id' => $request->user()->id,
                    'daily_record_id' => $dailyRecord->id,
                    'category_id' => $categoryId,
                    'parent_id' => $parentId,
                    'expense_group_id' => $group?->id,
                    'amount' => $line['amount'],
                    'gst_amount' => $line['gst_amount'] ?? 0,
                    'date' => $validated['date'],
                    'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
                    'description' => $validated['description'],
                    'payment_method' => $line['payment_method'],
                    'paid_by' => $paidByLabel,
                    'paid_by_type' => $paidByType,
                    'paid_by_friend_id' => $validated['paid_by_friend_id'] ?? null,
                    'split_with_friend_id' => $validated['split_with_friend_id'] ?? null,
                    'split_my_share' => $validated['split_my_share'] ?? null,
                    'split_friend_share' => $validated['split_friend_share'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'receipt_image' => $imagePath,
                    'is_voluntary' => $isVoluntary,
                ]);

                if (! $parentId) {
                    $linkService->syncExpenseFriendLink($expense);
                }
                AuditService::log('expense', $expense->id, 'created', null, $expense->toArray(), 'Expense created');

                return $expense;
            });
        });

        if ($parentId) {
            return redirect()->route('expenses.show', $parentId)->with('success', 'Sub-item added under parent expense.');
        }

        return redirect()->route('expenses.index')->with('success', $expenses->count() > 1 ? 'Grouped expense recorded successfully!' : 'Expense recorded successfully!');
    }

    public function group(Request $request)
    {
        $validated = $request->validate([
            'expense_ids' => 'required|array|min:2',
            'expense_ids.*' => 'integer|exists:expenses,id',
            'name' => 'nullable|string|max:255',
        ]);

        $expenses = Expense::where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->whereNull('expense_group_id')
            ->whereIn('id', $validated['expense_ids'])
            ->get();

        if ($expenses->count() !== count(array_unique($validated['expense_ids']))) {
            return back()->withInput()->with('error', 'Only your ungrouped top-level expenses can be grouped.');
        }

        $group = DB::transaction(function () use ($request, $validated, $expenses): ExpenseGroup {
            $group = ExpenseGroup::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'] ?? $expenses->first()->description,
            ]);
            $expenses->each->update(['expense_group_id' => $group->id]);

            return $group;
        });

        return redirect()->route('expenses.index')->with('success', 'Expenses grouped successfully.');
    }

    public function ungroup(Request $request, ExpenseGroup $expenseGroup)
    {
        if ($expenseGroup->user_id !== $request->user()->id) {
            abort(403);
        }

        DB::transaction(function () use ($expenseGroup): void {
            $expenseGroup->expenses()->update(['expense_group_id' => null]);
            $expenseGroup->delete();
        });

        return back()->with('success', 'Expense group removed. The expense rows were kept.');
    }

    public function renameGroup(Request $request, ExpenseGroup $expenseGroup)
    {
        if ($expenseGroup->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $expenseGroup->update(['name' => $validated['name']]);

        return back()->with('success', 'Expense group name updated.');
    }

    public function updateGroupPaymentMethod(Request $request, ExpenseGroup $expenseGroup, Expense $expense)
    {
        if ($expenseGroup->user_id !== $request->user()->id
            || $expense->user_id !== $request->user()->id
            || $expense->expense_group_id !== $expenseGroup->id) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|max:50',
        ]);

        $expense->update(['payment_method' => $validated['payment_method']]);

        return back()->with('success', 'Payment method updated.');
    }

    public function show(Expense $expense, OptionsService $options)
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        $expense->load(['category', 'subItems.category', 'foodEntries.category', 'paidByFriend', 'splitWithFriend', 'expenseGroup.expenses']);
        $unlinkableFoods = FoodEntry::where('user_id', auth()->id())
            ->whereDate('date', $expense->date)
            ->where(function ($q) use ($expense) {
                $q->whereNull('expense_id')->orWhere('expense_id', '!=', $expense->id);
            })
            ->orderByDesc('created_at')
            ->take(30)
            ->get();

        $paymentMethods = $options->names('payment_method');

        return view('finance.expenses.show', compact('expense', 'unlinkableFoods', 'paymentMethods'));
    }

    public function linkFood(Request $request, Expense $expense)
    {
        if ($expense->user_id !== $request->user()->id || $expense->parent_id) {
            abort(403);
        }

        $validated = $request->validate([
            'food_entry_ids' => 'required|array|min:1',
            'food_entry_ids.*' => 'exists:food_entries,id',
        ]);

        DB::transaction(function () use ($request, $validated, $expense): void {
            $foodEntries = FoodEntry::where('user_id', $request->user()->id)
                ->whereIn('id', $validated['food_entry_ids'])
                ->lockForUpdate()
                ->get();

            if ($foodEntries->count() !== count($validated['food_entry_ids'])) {
                abort(422, 'One or more selected food entries are unavailable.');
            }

            if ($foodEntries->contains(fn (FoodEntry $food): bool => $food->expense_id && $food->expense_id !== $expense->id)) {
                abort(422, 'A selected food entry is already linked to another expense.');
            }

            $requestedAmount = (float) $foodEntries
                ->whereNull('expense_id')
                ->sum('amount');
            if ($requestedAmount > $expense->remainingAmount()) {
                abort(422, 'The selected food entries exceed the remaining amount of this expense.');
            }

            $foodEntries->each->update(['expense_id' => $expense->id]);
        });

        return back()->with('success', 'Food/snack items linked under this expense.');
    }

    public function edit(Expense $expense, FinanceService $financeService, OptionsService $options)
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('expense', $expense)) {
            return redirect()->route('expenses.index')->with('error', 'This expense is locked and can no longer be edited.');
        }

        $categories = ExpenseCategory::orderBy('name')->get();
        $friends = Friend::where('user_id', auth()->id())->orderBy('name')->get();
        $paymentMethods = $options->names('payment_method');

        return view('finance.expenses.edit', compact('expense', 'categories', 'friends', 'paymentMethods'));
    }

    public function update(Request $request, Expense $expense, FinanceService $financeService, FinanceLinkService $linkService)
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('expense', $expense)) {
            return redirect()->route('expenses.index')->with('error', 'This expense is locked and can no longer be edited.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'gst_amount' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:expense_categories,id',
            'date' => 'required|date',
            'time' => 'nullable',
            'description' => 'required|string|max:255',
            'payment_method' => 'required|string',
            'paid_by_type' => 'nullable|in:me,friend',
            'paid_by_friend_id' => 'nullable|exists:friends,id',
            'split_with_friend_id' => 'nullable|exists:friends,id',
            'split_my_share' => 'nullable|numeric|min:0',
            'split_friend_share' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string|max:255',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_voluntary' => 'nullable|boolean',
        ]);

        $oldValues = $expense->only(['amount', 'gst_amount', 'category_id', 'date', 'description', 'payment_method']);

        if ($request->hasFile('receipt_image')) {
            $validated['receipt_image'] = $request->file('receipt_image')->store('receipts', 'public');
        }

        $paidByType = $validated['paid_by_type'] ?? 'me';
        $paidByLabel = 'Me';
        if ($paidByType === 'friend' && ! empty($validated['paid_by_friend_id'])) {
            $paidByLabel = Friend::find($validated['paid_by_friend_id'])?->name ?? 'Friend';
        }

        $category = ! empty($validated['category_id']) ? ExpenseCategory::find($validated['category_id']) : null;

        $expense->update([
            ...collect($validated)->except(['reason', 'paid_by_type'])->all(),
            'paid_by' => $paidByLabel,
            'paid_by_type' => $paidByType,
            'is_voluntary' => $request->boolean('is_voluntary') || ($category?->is_voluntary ?? false),
        ]);

        $linkService->syncExpenseFriendLink($expense);

        AuditService::log(
            module: 'expense',
            recordId: $expense->id,
            action: 'updated',
            oldValues: $oldValues,
            newValues: $expense->only(['amount', 'gst_amount', 'category_id', 'date', 'description', 'payment_method']),
            reason: $request->input('reason', 'Updated by user')
        );

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
    }

    public function destroy(Request $request, Expense $expense, FinanceLinkService $linkService)
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        AuditService::log(
            module: 'expense',
            recordId: $expense->id,
            action: 'deleted',
            oldValues: $expense->toArray(),
            reason: $request->input('reason', 'Deleted by user')
        );

        $linkService->removeExpenseFriendLink($expense);
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense moved to trash.');
    }
}
