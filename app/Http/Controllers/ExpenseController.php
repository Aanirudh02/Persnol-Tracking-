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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request, FinanceService $financeService, OptionsService $options): View
    {
        $user = $request->user();
        $query = Expense::query()
            ->where('user_id', $user->id)
            ->with(['category', 'parent', 'subItems', 'paidByFriend', 'expenseGroup.expenses.category', 'friendSplit.friend', 'friendSplits.friend']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method'));
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->string('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->string('to_date'));
        }
        if ($request->boolean('voluntary_only')) {
            $query->where('is_voluntary', true);
        }

        $expenses = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = ExpenseCategory::query()->orderBy('name')->get();
        $paymentMethods = $options->names('payment_method');
        $totalAmount = (float) $financeService->expenseBaseQuery(
            $user->id,
            $request->string('from_date')->toString() ?: null,
            $request->string('to_date')->toString() ?: null
        )->sum(DB::raw('expenses.amount + expenses.gst_amount'));

        return view('finance.expenses.index', compact('expenses', 'categories', 'totalAmount', 'paymentMethods'));
    }

    public function create(OptionsService $options): View
    {
        $categories = ExpenseCategory::query()
            ->where(function ($q) {
                $q->whereNull('is_archived')->orWhere('is_archived', false);
            })
            ->orderBy('name')
            ->get();

        $hasSnacks = $categories->contains(fn ($c) => strcasecmp($c->name, 'Snacks') === 0);
        if (! $hasSnacks) {
            ExpenseCategory::firstOrCreate(
                ['name' => 'Snacks', 'user_id' => null],
                ['icon' => 'cookie', 'color' => '#eab308', 'is_archived' => false, 'is_voluntary' => false]
            );
            $categories = ExpenseCategory::query()
                ->where(function ($q) {
                    $q->whereNull('is_archived')->orWhere('is_archived', false);
                })
                ->orderBy('name')
                ->get();
        }

        $friends = Friend::query()->where('user_id', auth()->id())->orderBy('name')->get();
        $paymentMethods = $options->names('payment_method');
        if (! in_array('Split', $paymentMethods, true)) {
            $paymentMethods[] = 'Split';
        }
        $parents = Expense::query()->where('user_id', auth()->id())->whereNull('parent_id')->latest()->take(30)->get();

        return view('finance.expenses.create', compact('categories', 'friends', 'paymentMethods', 'parents'));
    }

    public function store(Request $request, FinanceLinkService $linkService): RedirectResponse
    {
        $validated = $this->validateExpense($request);
        $isGrouped = $request->boolean('add_group_expense') && empty($validated['parent_id']);
        $gstAmount = round((float) ($validated['gst_amount'] ?? 0), 2);
        $groupLines = collect($validated['group_expenses'] ?? [])
            ->filter(fn (array $line): bool => filled($line['amount'] ?? null) && filled($line['payment_method'] ?? null))
            ->values();

        $totalBill = (float) $validated['amount'] + $gstAmount;
        $splitData = $this->buildSplitData($validated, $totalBill);

        // If friend paid a portion, register user's paid amount alone in their expenses
        $paidByMe = $splitData ? (float) ($splitData['paid_by_me_amount'] ?? 0) : null;
        $paidByFriends = $splitData ? (float) ($splitData['paid_by_friend_amount'] ?? 0) : 0;
        $hasExplicitShare = $splitData && (($splitData['my_share'] !== null && (float) $splitData['my_share'] > 0) || ($splitData['friend_share'] !== null && (float) $splitData['friend_share'] > 0));

        $registeredAmount = (float) $validated['amount'];
        $registeredGst = $gstAmount;

        if ($splitData !== null && $paidByFriends > 0) {
            $targetUserSpend = $hasExplicitShare && (float) ($splitData['my_share'] ?? 0) > 0
                ? (float) $splitData['my_share']
                : ($paidByMe !== null ? $paidByMe : (float) $validated['amount']);

            if ($targetUserSpend < $totalBill) {
                $registeredAmount = round($targetUserSpend, 2);
                $registeredGst = 0.0;
                $payerNote = 'Total bill: ₹'.number_format($totalBill, 2).' (You paid: ₹'.number_format($paidByMe ?? $registeredAmount, 2).', Friend(s) paid: ₹'.number_format($paidByFriends, 2).')';
                $validated['notes'] = trim(($validated['notes'] ?? '')."\n".$payerNote);
            }
        }

        if ($isGrouped) {
            if ($groupLines->count() < 2) {
                return back()->withInput()->withErrors(['group_expenses' => 'Add at least two payment lines for a payment breakdown.']);
            }

            $groupTotal = round((float) $groupLines->sum(fn (array $line): float => (float) $line['amount']), 2);
            // If friend split is active, payment lines break down what the user paid
            $expectedBreakdown = $splitData ? (float) ($splitData['paid_by_me_amount'] ?? 0) : (float) $validated['amount'];

            if ($splitData && $expectedBreakdown <= 0) {
                // Friend paid entire bill; breakdown of user payment is not needed
            } elseif (abs($groupTotal - $expectedBreakdown) > 0.05) {
                $targetLabel = $splitData ? 'your payment share' : 'the total expense amount';

                return back()->withInput()->withErrors(['group_expenses' => "Payment breakdown must add up to {$targetLabel} (₹".number_format($expectedBreakdown, 2).').']);
            }
        }

        $imagePath = null;
        if ($request->hasFile('receipt_image')) {
            $imagePath = $request->file('receipt_image')->store('receipts', 'public');
        }

        $parentId = $validated['parent_id'] ?? null;
        $categoryId = $validated['category_id'] ?? null;
        if ($parentId) {
            $parent = Expense::query()->where('user_id', $request->user()->id)->findOrFail($parentId);
            $categoryId = $parent->category_id;
            if ((float) $validated['amount'] + $gstAmount > $parent->remainingAmount()) {
                return back()->withInput()->withErrors(['amount' => 'This sub-item exceeds the remaining amount of the parent expense.']);
            }
            if ($splitData !== null) {
                return back()->withInput()->withErrors(['split_with_friend_id' => 'Friend split tracking is only supported on top-level expenses.']);
            }
        }

        $category = $categoryId ? ExpenseCategory::query()->find($categoryId) : null;
        $isVoluntary = $request->boolean('is_voluntary') || ($category?->is_voluntary ?? false);
        [$paidByLabel, $paidByType, $paidByFriendId] = $this->displayPayerFields($splitData);

        $expenses = DB::transaction(function () use ($request, $validated, $imagePath, $paidByLabel, $paidByType, $paidByFriendId, $parentId, $categoryId, $isVoluntary, $groupLines, $registeredGst, $isGrouped, $splitData, $registeredAmount) {
            $dailyRecord = DailyRecord::query()
                ->where('user_id', $request->user()->id)
                ->whereDate('record_date', $validated['date'])
                ->first();

            if (! $dailyRecord) {
                $dailyRecord = DailyRecord::create([
                    'user_id' => $request->user()->id,
                    'record_date' => $validated['date'],
                ]);
            }

            // If friend split is active with grouped lines, record as a single top-level expense with payment breakdown in notes
            if ($splitData !== null && $isGrouped) {
                $breakdownText = 'Payment breakdown: '.$groupLines->map(fn (array $line) => ($line['payment_method'] ?? 'Other').' ₹'.number_format((float) $line['amount'], 2))->implode(', ');
                $combinedNotes = trim(($validated['notes'] ?? '')."\n".$breakdownText);
                $combinedMethod = $groupLines->pluck('payment_method')->unique()->implode(' + ');

                $expense = Expense::create([
                    'user_id' => $request->user()->id,
                    'daily_record_id' => $dailyRecord->id,
                    'category_id' => $categoryId,
                    'parent_id' => $parentId,
                    'expense_group_id' => null,
                    'amount' => $registeredAmount,
                    'gst_amount' => $registeredGst,
                    'date' => $validated['date'],
                    'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
                    'description' => $validated['description'],
                    'payment_method' => $combinedMethod ?: $validated['payment_method'],
                    'paid_by' => $paidByLabel,
                    'paid_by_type' => $paidByType,
                    'paid_by_friend_id' => $paidByFriendId,
                    'split_with_friend_id' => $splitData['friend_id'] ?? null,
                    'split_my_share' => $splitData['my_share'] ?? null,
                    'split_friend_share' => $splitData['friend_share'] ?? null,
                    'notes' => $combinedNotes,
                    'receipt_image' => $imagePath,
                    'is_voluntary' => $isVoluntary,
                ]);

                AuditService::log('expense', $expense->id, 'created', null, $expense->toArray(), 'Expense created with friend split');

                return collect([$expense]);
            }

            $group = ($isGrouped && $splitData === null)
                ? ExpenseGroup::create([
                    'user_id' => $request->user()->id,
                    'name' => $validated['description'],
                ])
                : null;

            $lines = ($isGrouped && $splitData === null)
                ? $groupLines
                : collect([[
                    'amount' => $registeredAmount,
                    'payment_method' => $validated['payment_method'],
                ]]);

            $baseTotal = (float) $lines->sum(fn (array $line): float => (float) $line['amount']);
            $allocatedGst = $lines->values()->map(function (array $line, int $index) use ($registeredGst, $baseTotal, $lines): array {
                $gst = $index === $lines->count() - 1
                    ? $registeredGst - (float) $lines->slice(0, $index)->sum('gst_amount')
                    : ($baseTotal > 0 ? round($registeredGst * ((float) $line['amount'] / $baseTotal), 2) : 0);

                $line['gst_amount'] = round($gst, 2);

                return $line;
            });

            return $allocatedGst->map(function (array $line) use ($request, $validated, $imagePath, $paidByLabel, $paidByType, $paidByFriendId, $parentId, $categoryId, $isVoluntary, $dailyRecord, $group, $splitData): Expense {
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
                    'paid_by_friend_id' => $paidByFriendId,
                    'split_with_friend_id' => $splitData['friend_id'] ?? null,
                    'split_my_share' => $splitData['my_share'] ?? null,
                    'split_friend_share' => $splitData['friend_share'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'receipt_image' => $imagePath,
                    'is_voluntary' => $isVoluntary,
                ]);

                AuditService::log('expense', $expense->id, 'created', null, $expense->toArray(), 'Expense created');

                return $expense;
            });
        });

        if ($splitData && $expenses->count() === 1) {
            $linkService->syncExpenseFriendLink($expenses->first(), $splitData);
        }

        if ($parentId) {
            return redirect()->route('expenses.show', $parentId)->with('success', 'Sub-item added under parent expense.');
        }

        return redirect()->route('expenses.index')->with('success', $expenses->count() > 1 ? 'Grouped expense recorded successfully!' : 'Expense recorded successfully!');
    }

    public function group(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_ids' => 'required|array|min:2',
            'expense_ids.*' => 'integer|exists:expenses,id',
            'name' => 'nullable|string|max:255',
        ]);

        $expenses = Expense::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('expense_group_id')
            ->whereIn('id', $validated['expense_ids'])
            ->get();

        if ($expenses->count() !== count(array_unique($validated['expense_ids']))) {
            return back()->withInput()->with('error', 'Select at least two of your own expenses that are not already in a group. Parent, sub-expense, and friend-split expenses can be grouped.');
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

    public function ungroup(Request $request, ExpenseGroup $expenseGroup): RedirectResponse
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

    public function renameGroup(Request $request, ExpenseGroup $expenseGroup): RedirectResponse
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

    public function updateGroupPaymentMethod(Request $request, ExpenseGroup $expenseGroup, Expense $expense): RedirectResponse
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

    public function show(Expense $expense, OptionsService $options): View
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        $expense->load(['category', 'subItems.category', 'foodEntries.category', 'paidByFriend', 'splitWithFriend', 'expenseGroup.expenses', 'friendSplit.friend', 'friendSplits.friend']);
        $unlinkableFoods = FoodEntry::query()
            ->where('user_id', auth()->id())
            ->whereDate('date', $expense->date)
            ->where(function ($query) use ($expense) {
                $query->whereNull('expense_id')->orWhere('expense_id', '!=', $expense->id);
            })
            ->orderByDesc('created_at')
            ->take(30)
            ->get();

        $paymentMethods = $options->names('payment_method');

        return view('finance.expenses.show', compact('expense', 'unlinkableFoods', 'paymentMethods'));
    }

    public function linkFood(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->user_id !== $request->user()->id || $expense->parent_id) {
            abort(403);
        }

        $validated = $request->validate([
            'food_entry_ids' => 'required|array|min:1',
            'food_entry_ids.*' => 'exists:food_entries,id',
        ]);

        DB::transaction(function () use ($request, $validated, $expense): void {
            $foodEntries = FoodEntry::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('id', $validated['food_entry_ids'])
                ->lockForUpdate()
                ->get();

            if ($foodEntries->count() !== count($validated['food_entry_ids'])) {
                abort(422, 'One or more selected food entries are unavailable.');
            }

            if ($foodEntries->contains(fn (FoodEntry $food): bool => $food->expense_id && $food->expense_id !== $expense->id)) {
                abort(422, 'A selected food entry is already linked to another expense.');
            }

            $requestedAmount = (float) $foodEntries->whereNull('expense_id')->sum('amount');
            if ($requestedAmount > $expense->remainingAmount()) {
                abort(422, 'The selected food entries exceed the remaining amount of this expense.');
            }

            $foodEntries->each->update(['expense_id' => $expense->id]);
        });

        return back()->with('success', 'Food/snack items linked under this expense.');
    }

    public function edit(Expense $expense, FinanceService $financeService, OptionsService $options): View|RedirectResponse
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('expense', $expense)) {
            return redirect()->route('expenses.index')->with('error', 'This expense is locked and can no longer be edited.');
        }

        $expense->load(['friendSplits.friend', 'friendSplit.friend']);
        $categories = ExpenseCategory::query()
            ->where(function ($q) {
                $q->whereNull('is_archived')->orWhere('is_archived', false);
            })
            ->orderBy('name')
            ->get();
        $friends = Friend::query()->where('user_id', auth()->id())->orderBy('name')->get();
        $paymentMethods = $options->names('payment_method');
        if (! in_array('Split', $paymentMethods, true)) {
            $paymentMethods[] = 'Split';
        }

        return view('finance.expenses.edit', compact('expense', 'categories', 'friends', 'paymentMethods'));
    }

    public function update(Request $request, Expense $expense, FinanceService $financeService, FinanceLinkService $linkService): RedirectResponse
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('expense', $expense)) {
            return redirect()->route('expenses.index')->with('error', 'This expense is locked and can no longer be edited.');
        }

        $validated = $this->validateExpense($request, false);
        $oldValues = $expense->only(['amount', 'gst_amount', 'category_id', 'date', 'description', 'payment_method']);

        if ($request->hasFile('receipt_image')) {
            $validated['receipt_image'] = $request->file('receipt_image')->store('receipts', 'public');
        }

        $category = ! empty($validated['category_id']) ? ExpenseCategory::query()->find($validated['category_id']) : null;
        $totalBill = round((float) $validated['amount'] + (float) ($validated['gst_amount'] ?? 0), 2);
        $splitData = $this->buildSplitData($validated, $totalBill);
        [$paidByLabel, $paidByType, $paidByFriendId] = $this->displayPayerFields($splitData);

        $paidByMe = $splitData ? (float) ($splitData['paid_by_me_amount'] ?? 0) : null;
        $paidByFriends = $splitData ? (float) ($splitData['paid_by_friend_amount'] ?? 0) : 0;
        $hasExplicitShare = $splitData && (($splitData['my_share'] !== null && (float) $splitData['my_share'] > 0) || ($splitData['friend_share'] !== null && (float) $splitData['friend_share'] > 0));

        $registeredAmount = (float) $validated['amount'];
        $registeredGst = (float) ($validated['gst_amount'] ?? 0);

        if ($splitData !== null && $paidByFriends > 0) {
            $targetUserSpend = $hasExplicitShare && (float) ($splitData['my_share'] ?? 0) > 0
                ? (float) $splitData['my_share']
                : ($paidByMe !== null ? $paidByMe : (float) $validated['amount']);

            if ($targetUserSpend < $totalBill) {
                $registeredAmount = round($targetUserSpend, 2);
                $registeredGst = 0.0;
                $payerNote = 'Total bill: ₹'.number_format($totalBill, 2).' (You paid: ₹'.number_format($paidByMe ?? $registeredAmount, 2).', Friend(s) paid: ₹'.number_format($paidByFriends, 2).')';
                if (! str_contains($validated['notes'] ?? '', 'Total bill:')) {
                    $validated['notes'] = trim(($validated['notes'] ?? '')."\n".$payerNote);
                }
            }
        }
        $validated['amount'] = $registeredAmount;
        $validated['gst_amount'] = $registeredGst;

        $expense->update([
            ...collect($validated)->except([
                'reason',
                'split_paid_by_type',
                'split_paid_by_me_amount',
                'split_paid_by_friend_amount',
                'splits',
                'record_as_combination',
            ])->all(),
            'paid_by' => $paidByLabel,
            'paid_by_type' => $paidByType,
            'paid_by_friend_id' => $paidByFriendId,
            'split_with_friend_id' => $splitData['friend_id'] ?? null,
            'split_my_share' => $splitData['my_share'] ?? null,
            'split_friend_share' => $splitData['friend_share'] ?? null,
            'is_voluntary' => $request->boolean('is_voluntary') || ($category?->is_voluntary ?? false),
        ]);

        if ($splitData) {
            $linkService->syncExpenseFriendLink($expense, $splitData);
        } else {
            $linkService->removeExpenseFriendLink($expense);
        }

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

    public function destroy(Request $request, Expense $expense, FinanceLinkService $linkService): RedirectResponse
    {
        if ($expense->user_id !== auth()->id()) {
            abort(403);
        }

        if ($expense->subItems()->exists() || $expense->foodEntries()->exists()) {
            return back()->with('error', 'Cannot delete expense with linked sub-items or food entries.');
        }

        $linkService->removeExpenseFriendLink($expense);

        AuditService::log(
            module: 'expense',
            recordId: $expense->id,
            action: 'deleted',
            oldValues: $expense->toArray(),
            reason: 'Deleted by user'
        );

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully!');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateExpense(Request $request, bool $isCreate = true): array
    {
        $rules = [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'gst_amount' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:expense_categories,id'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'description' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'max:50'],
            'receipt_image' => ['nullable', 'image', 'max:5120'],
            'notes' => ['nullable', 'string'],
            'is_voluntary' => ['nullable', 'boolean'],
            'record_as_combination' => ['nullable', 'boolean'],
            'split_with_friend_id' => ['nullable', 'integer'],
            'split_my_share' => ['nullable', 'numeric', 'min:0'],
            'split_friend_share' => ['nullable', 'numeric', 'min:0'],
            'split_paid_by_type' => ['nullable', 'in:me,friend,split'],
            'split_paid_by_me_amount' => ['nullable', 'numeric', 'min:0'],
            'split_paid_by_friend_amount' => ['nullable', 'numeric', 'min:0'],
            'splits' => ['nullable', 'array'],
            'splits.*.friend_id' => ['nullable', 'integer'],
            'splits.*.friend_share' => ['nullable', 'numeric', 'min:0'],
            'splits.*.paid_by_friend_amount' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($isCreate) {
            $rules['parent_id'] = ['nullable', 'integer', 'exists:expenses,id'];
            $rules['add_group_expense'] = ['nullable', 'boolean'];
            $rules['group_expenses'] = ['nullable', 'array', 'min:2'];
            $rules['group_expenses.*.amount'] = ['required_with:add_group_expense', 'numeric', 'min:0.01'];
            $rules['group_expenses.*.payment_method'] = ['required_with:add_group_expense', 'string', 'max:50'];
        }

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $rawSplits = collect($request->input('splits', []))
                ->filter(fn ($s) => ! empty($s['friend_id']) && (int) $s['friend_id'] > 0)
                ->values();

            $singleFriendId = $request->integer('split_with_friend_id');

            if ($rawSplits->isEmpty() && ! $singleFriendId) {
                return;
            }

            $total = round((float) $request->input('amount') + (float) $request->input('gst_amount', 0), 2);

            if ($rawSplits->isNotEmpty()) {
                $friendIds = $rawSplits->pluck('friend_id')->all();
                $userFriendsCount = Friend::query()
                    ->where('user_id', $request->user()->id)
                    ->whereIn('id', $friendIds)
                    ->count();

                if ($userFriendsCount !== count(array_unique($friendIds))) {
                    $validator->errors()->add('splits', 'Choose friends from your own friends list.');

                    return;
                }

                $friendsSharesTotal = round((float) $rawSplits->sum(fn ($s) => (float) ($s['friend_share'] ?? 0)), 2);
                $friendsPaidTotal = round((float) $rawSplits->sum(fn ($s) => (float) ($s['paid_by_friend_amount'] ?? 0)), 2);
                $myShare = round((float) $request->input('split_my_share', 0), 2);
                $paidByMe = round((float) $request->input('split_paid_by_me_amount', 0), 2);

                $paidMatchesTotal = abs(($paidByMe + $friendsPaidTotal) - $total) <= 0.05;
                $hasExplicitShares = ($myShare > 0 || $friendsSharesTotal > 0);
                $isCombinationMode = $request->boolean('record_as_combination')
                    || ($paidMatchesTotal && ! $hasExplicitShares);

                if (! $isCombinationMode && abs(($myShare + $friendsSharesTotal) - $total) > 0.05) {
                    $validator->errors()->add('split_my_share', 'Total shares (your share ₹'.number_format($myShare, 2).' + friends ₹'.number_format($friendsSharesTotal, 2).') must equal total expense ₹'.number_format($total, 2).'.');
                }

                if (abs(($paidByMe + $friendsPaidTotal) - $total) > 0.05) {
                    $validator->errors()->add('split_paid_by_me_amount', 'Total paid amounts (you ₹'.number_format($paidByMe, 2).' + friends ₹'.number_format($friendsPaidTotal, 2).') must equal total expense ₹'.number_format($total, 2).'.');
                }
            } else {
                $friendExists = Friend::query()
                    ->where('user_id', $request->user()->id)
                    ->whereKey($singleFriendId)
                    ->exists();

                if (! $friendExists) {
                    $validator->errors()->add('split_with_friend_id', 'Choose one of your own friends.');

                    return;
                }

                $myShare = round((float) $request->input('split_my_share', 0), 2);
                $friendShare = round((float) $request->input('split_friend_share', 0), 2);

                $mode = $request->input('split_paid_by_type', 'me');
                $paidByMe = match ($mode) {
                    'friend' => 0.0,
                    'split' => round((float) $request->input('split_paid_by_me_amount', 0), 2),
                    default => $total,
                };
                $paidByFriend = $mode === 'me'
                    ? 0.0
                    : ($mode === 'split'
                        ? round((float) $request->input('split_paid_by_friend_amount', 0), 2)
                        : $total);

                $paidMatchesTotal = abs(($paidByMe + $paidByFriend) - $total) <= 0.05;
                $hasExplicitShares = ($myShare > 0 || $friendShare > 0);
                $isCombinationMode = $request->boolean('record_as_combination')
                    || ($paidMatchesTotal && ! $hasExplicitShares);

                if (! $isCombinationMode) {
                    if ($myShare <= 0 && $friendShare <= 0) {
                        $validator->errors()->add('split_my_share', 'Enter at least one split share.');
                    }

                    if (abs(($myShare + $friendShare) - $total) > 0.05) {
                        $validator->errors()->add('split_my_share', 'Split shares must add up to the full expense total including GST.');
                    }
                }

                if (abs(($paidByMe + $paidByFriend) - $total) > 0.05) {
                    $validator->errors()->add('split_paid_by_me_amount', 'Actual paid amounts must add up to the full expense total.');
                }
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>|null
     */
    private function buildSplitData(array $validated, float $total): ?array
    {
        $multiSplits = collect($validated['splits'] ?? [])
            ->filter(fn ($s) => ! empty($s['friend_id']) && (int) $s['friend_id'] > 0)
            ->values();

        if ($multiSplits->isNotEmpty()) {
            $friendSharesTotal = round((float) $multiSplits->sum(fn ($s) => (float) ($s['friend_share'] ?? 0)), 2);
            $friendsPaidTotal = round((float) $multiSplits->sum(fn ($s) => (float) ($s['paid_by_friend_amount'] ?? 0)), 2);
            $myShare = round((float) ($validated['split_my_share'] ?? 0), 2);
            $paidByMe = round((float) ($validated['split_paid_by_me_amount'] ?? 0), 2);

            $paidMatchesTotal = abs(($paidByMe + $friendsPaidTotal) - $total) <= 0.05;
            $hasExplicitShares = ($myShare > 0 || $friendSharesTotal > 0);
            $isCombination = ! empty($validated['record_as_combination']) || ($paidMatchesTotal && ! $hasExplicitShares);

            if ($isCombination && ! $hasExplicitShares) {
                $myShare = null;
                $friendSharesTotal = null;
                $multiSplits = $multiSplits->map(function ($s) {
                    $paid = round((float) ($s['paid_by_friend_amount'] ?? 0), 2);
                    $s['friend_share'] = null;
                    $s['paid_by_friend_amount'] = $paid;

                    return $s;
                });
            } else {
                $myShare = round((float) ($validated['split_my_share'] ?? max(0, $total - $friendSharesTotal)), 2);
                $paidByMe = round((float) ($validated['split_paid_by_me_amount'] ?? max(0, $total - $friendsPaidTotal)), 2);
            }

            return [
                'friend_id' => (int) $multiSplits->first()['friend_id'],
                'my_share' => $myShare,
                'friend_share' => $friendSharesTotal,
                'paid_by_mode' => ($paidByMe > 0 && $friendsPaidTotal > 0) ? 'split' : ($paidByMe > 0 ? 'me' : 'friend'),
                'paid_by_me_amount' => $paidByMe,
                'paid_by_friend_amount' => $friendsPaidTotal,
                'multi_splits' => $multiSplits->map(fn ($s) => [
                    'friend_id' => (int) $s['friend_id'],
                    'friend_share' => $s['friend_share'] !== null ? round((float) $s['friend_share'], 2) : null,
                    'paid_by_friend_amount' => round((float) ($s['paid_by_friend_amount'] ?? 0), 2),
                ])->all(),
                'notes' => $validated['notes'] ?? null,
            ];
        }

        $friendId = (int) ($validated['split_with_friend_id'] ?? 0);
        if ($friendId <= 0) {
            return null;
        }

        $mode = $validated['split_paid_by_type'] ?? 'me';
        $paidByMe = match ($mode) {
            'friend' => 0.0,
            'split' => round((float) ($validated['split_paid_by_me_amount'] ?? 0), 2),
            default => $total,
        };
        $paidByFriend = round($total - $paidByMe, 2);
        $myShare = round((float) ($validated['split_my_share'] ?? 0), 2);

        $hasExplicitShares = ($myShare > 0 || (isset($validated['split_friend_share']) && (float) $validated['split_friend_share'] > 0));
        $paidMatchesTotal = abs(($paidByMe + $paidByFriend) - $total) <= 0.05;
        $isCombination = ! empty($validated['record_as_combination']) || ($paidMatchesTotal && ! $hasExplicitShares);

        if ($isCombination && ! $hasExplicitShares) {
            $myShare = null;
            $friendShare = null;
        } else {
            $friendShare = round((float) ($validated['split_friend_share'] ?? ($total - $myShare)), 2);
        }

        return [
            'friend_id' => $friendId,
            'my_share' => $myShare,
            'friend_share' => $friendShare,
            'paid_by_mode' => $mode,
            'paid_by_me_amount' => $paidByMe,
            'paid_by_friend_amount' => $paidByFriend,
            'multi_splits' => [
                [
                    'friend_id' => $friendId,
                    'friend_share' => $friendShare,
                    'paid_by_friend_amount' => $paidByFriend,
                ],
            ],
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $splitData
     * @return array{0: string, 1: string, 2: int|null}
     */
    private function displayPayerFields(?array $splitData): array
    {
        if ($splitData === null) {
            return ['Me', 'me', null];
        }

        if (! empty($splitData['multi_splits']) && count($splitData['multi_splits']) > 1) {
            $friendNames = Friend::query()
                ->whereIn('id', collect($splitData['multi_splits'])->pluck('friend_id'))
                ->pluck('name')
                ->implode(', ');

            $paidByMe = (float) ($splitData['paid_by_me_amount'] ?? 0);
            $paidByFriends = (float) ($splitData['paid_by_friend_amount'] ?? 0);

            if ($paidByMe > 0 && $paidByFriends > 0) {
                return ["Split: You & {$friendNames}", 'split', (int) $splitData['friend_id']];
            } elseif ($paidByFriends > 0) {
                return [$friendNames, 'friend', (int) $splitData['friend_id']];
            } else {
                return ['Me', 'me', (int) $splitData['friend_id']];
            }
        }

        $friend = Friend::query()->find($splitData['friend_id']);

        return match ($splitData['paid_by_mode']) {
            'friend' => [$friend?->name ?? 'Friend', 'friend', $friend?->id],
            'split' => ['Split Payment', 'split', $friend?->id],
            default => ['Me', 'me', $friend?->id],
        };
    }
}
