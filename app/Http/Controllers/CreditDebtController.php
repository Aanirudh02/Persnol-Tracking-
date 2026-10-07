<?php

namespace App\Http\Controllers;

use App\Models\CreditDebt;
use App\Models\CreditDebtPayment;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Friend;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Services\CreditDebtLinkService;
use App\Services\FinanceLinkService;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreditDebtController extends Controller
{
    public function index(Request $request, OptionsService $options): View
    {
        $type = $request->get('type', 'credit');
        if (! in_array($type, ['credit', 'debt'], true)) {
            $type = 'credit';
        }

        $items = CreditDebt::query()
            ->where('user_id', $request->user()->id)
            ->where('type', $type)
            ->with(['friend', 'payments', 'linkedExpense', 'linkedPersonalExpense', 'linkedExpenses', 'linkedPersonalExpenses', 'linkedIncomes'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $friends = Friend::query()->where('user_id', $request->user()->id)->orderBy('name')->get();
        $personalCategories = PersonalExpenseCategory::query()
            ->where(fn ($q) => $q->where('user_id', $request->user()->id)->orWhereNull('user_id'))
            ->where('is_archived', false)
            ->orderBy('name')
            ->get();

        $openTotal = (float) CreditDebt::query()
            ->where('user_id', $request->user()->id)
            ->where('type', $type)
            ->whereNotIn('status', ['fully_paid'])
            ->get()
            ->sum(fn ($item) => $item->remaining());

        $statuses = $options->for('credit_status');
        $paymentMethods = $options->names('payment_method');

        return view('finance.credits.index', compact('items', 'friends', 'type', 'openTotal', 'statuses', 'paymentMethods', 'personalCategories'));
    }

    public function store(Request $request, FinanceLinkService $linkService): RedirectResponse
    {
        $validated = $this->validateCreditDebt($request, true);
        $friend = Friend::query()->where('user_id', $request->user()->id)->findOrFail($validated['friend_id']);

        $item = DB::transaction(function () use ($request, $validated, $friend): CreditDebt {
            $item = CreditDebt::create([
                'user_id' => $request->user()->id,
                'friend_id' => $friend->id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'amount_paid' => 0,
                'payment_method' => $validated['payment_method'] ?? null,
                'date' => $validated['date'],
                'location' => $validated['location'] ?? null,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'source' => 'manual',
                'status' => $validated['status'] ?? 'yet_to_pay',
            ]);

            $initialPaid = round((float) ($validated['amount_paid'] ?? 0), 2);
            if ($initialPaid > 0) {
                CreditDebtPayment::create([
                    'credit_debt_id' => $item->id,
                    'amount' => $initialPaid,
                    'paid_on' => $validated['date'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'notes' => 'Initial paid amount',
                ]);
            }

            $this->refreshCreditDebtAmounts($item);

            // Optional auto-link as normal expense on creation
            if ($request->boolean('link_as_expense')) {
                $categoryName = $item->type === 'debt' ? 'Loan / Lending' : 'Debt Repayment';
                $category = ExpenseCategory::firstOrCreate(
                    ['name' => $categoryName, 'user_id' => null],
                    ['icon' => 'hand-coins', 'color' => '#6366f1', 'is_archived' => false, 'is_voluntary' => false]
                );

                $dailyRecord = DailyRecord::firstOrCreate([
                    'user_id' => $request->user()->id,
                    'record_date' => $validated['date'],
                ]);

                // For a credit this expense IS the spend the friend covered; repaying it later
                // is only settling the credit and must not create a second expense.
                $desc = $item->type === 'debt'
                    ? 'Lent to '.$friend->name.($item->description ? ' ('.$item->description.')' : '')
                    : 'On credit from '.$friend->name.($item->description ? ' ('.$item->description.')' : '');

                $expense = Expense::create([
                    'user_id' => $request->user()->id,
                    'credit_debt_id' => $item->id,
                    'daily_record_id' => $dailyRecord->id,
                    'category_id' => $category->id,
                    'amount' => $item->amount,
                    'gst_amount' => 0,
                    'date' => $validated['date'],
                    'time' => Carbon::now()->format('H:i'),
                    'description' => $desc,
                    'payment_method' => $validated['payment_method'] ?: 'Cash',
                    'paid_by' => 'Me',
                    'paid_by_type' => 'me',
                    'notes' => trim(($validated['notes'] ?? '')."\nLinked to ".ucfirst($item->type).' #'.$item->id),
                    'is_voluntary' => false,
                ]);

                $item->linked_expense_id = $expense->id;
                $item->save();
            }

            return $item->fresh();
        });

        $linkService->syncCreditDebtToFriend($item);

        return back()->with('success', ucfirst($item->type).' recorded.');
    }

    public function edit(Request $request, CreditDebt $creditDebt, OptionsService $options, CreditDebtLinkService $creditLinkService): View
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $friends = Friend::query()->where('user_id', $request->user()->id)->orderBy('name')->get();
        $statuses = $options->for('credit_status');
        $paymentMethods = $options->names('payment_method');
        $creditDebt->load(['payments' => fn ($query) => $query->orderByDesc('paid_on')->orderByDesc('created_at')]);

        $linkedRecords = $creditLinkService->describeLinkedRecords($creditDebt);
        foreach ($creditDebt->payments as $payment) {
            $linkedRecords[] = [
                'label' => 'Payment ₹'.number_format((float) $payment->amount, 2).' · '.($payment->payment_method ?: 'No method').' · '.$payment->paid_on?->format('d M Y'),
                'url' => '',
            ];
        }

        return view('finance.credits.edit', compact('creditDebt', 'friends', 'statuses', 'paymentMethods', 'linkedRecords'));
    }

    public function update(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService, CreditDebtLinkService $creditLinkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $this->validateCreditDebt($request, true);
        $friend = Friend::query()->where('user_id', $request->user()->id)->findOrFail($validated['friend_id']);
        $newPaymentMethod = filled($validated['payment_method'] ?? null) ? $validated['payment_method'] : $creditDebt->payment_method;

        // Linked records/payments only change when the user ticked them in the prompt
        $before = [
            'payment_method' => $creditDebt->payment_method,
            'date' => $creditDebt->date?->toDateString(),
            'amount' => (float) $creditDebt->amount,
        ];
        $fieldsToSync = $creditLinkService->requestedFields($request->input('sync_linked', []));
        $syncSummary = [];

        DB::transaction(function () use ($creditDebt, $validated, $friend, $newPaymentMethod, $before, $fieldsToSync, $creditLinkService, &$syncSummary): void {
            $creditDebt->update([
                'friend_id' => $friend->id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'payment_method' => $newPaymentMethod,
                'date' => $validated['date'],
                'location' => $validated['location'] ?? null,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'status' => $validated['status'] ?? $creditDebt->status,
            ]);

            if (array_key_exists('amount_paid', $validated)) {
                $targetPaid = round((float) $validated['amount_paid'], 2);
                $existingPaymentsCount = $creditDebt->payments()->count();

                if ($existingPaymentsCount === 0) {
                    if ($targetPaid > 0) {
                        CreditDebtPayment::create([
                            'credit_debt_id' => $creditDebt->id,
                            'amount' => $targetPaid,
                            'paid_on' => $validated['date'],
                            'payment_method' => $newPaymentMethod,
                            'notes' => 'Direct paid update',
                        ]);
                    }
                } elseif ($existingPaymentsCount === 1) {
                    $singlePayment = $creditDebt->payments()->first();
                    if ($targetPaid <= 0) {
                        $singlePayment->delete();
                    } elseif (abs($targetPaid - (float) $singlePayment->amount) >= 0.01) {
                        $singlePayment->update(['amount' => $targetPaid]);
                    }
                } else {
                    // Multiple payments exist; if targetPaid differs from current total, adjust/reconcile the payments
                    $currentPaid = (float) $creditDebt->payments()->sum('amount');
                    $diff = round($targetPaid - $currentPaid, 2);

                    if (abs($diff) > 0.001) {
                        // Consolidate payments to accurately reflect the user-entered paid amount
                        $creditDebt->payments()->delete();
                        if ($targetPaid > 0) {
                            CreditDebtPayment::create([
                                'credit_debt_id' => $creditDebt->id,
                                'amount' => $targetPaid,
                                'paid_on' => $validated['date'],
                                'payment_method' => $newPaymentMethod,
                                'notes' => 'Adjusted paid amount',
                            ]);
                        }
                    }
                }
            }

            $this->refreshCreditDebtAmounts($creditDebt);

            // Payment method / date / amount reach payments and linked records only when confirmed
            $syncSummary = $creditLinkService->syncFromCreditDebt($creditDebt->fresh(['payments']), $before, $fieldsToSync);
        });

        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return redirect()->route('credits.index', ['type' => $creditDebt->type])
            ->with('success', trim(ucfirst($creditDebt->type).' updated. '.implode(' ', $syncSummary)));
    }

    public function destroy(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $type = $creditDebt->type;
        $deleteLinkedRecords = $request->boolean('delete_linked_expense');

        DB::transaction(function () use ($creditDebt, $deleteLinkedRecords, $linkService): void {
            // Every expense / personal expense / income filed from it — not only linked_expense_id
            $linkedRecords = collect()
                ->concat($creditDebt->linkedExpenses()->get())
                ->concat($creditDebt->linkedPersonalExpenses()->get())
                ->concat($creditDebt->linkedIncomes()->get());

            foreach ($linkedRecords as $record) {
                $deleteLinkedRecords
                    ? $record->delete()
                    : $record->update(['credit_debt_id' => null]);
            }

            $linkService->removeCreditDebtLink($creditDebt);
            $creditDebt->payments()->delete();
            $creditDebt->delete();
        });

        $msg = ucfirst($type).' deleted'.($deleteLinkedRecords ? ' along with its linked expenses / income.' : '; linked records were kept.');

        return redirect()->route('credits.index', ['type' => $type])->with('success', $msg);
    }

    public function addPayment(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ((float) $validated['amount'] - $creditDebt->remaining() > 0.01) {
            return back()->withInput()->withErrors(['amount' => 'Payment amount cannot exceed the remaining balance.']);
        }

        CreditDebtPayment::create([
            'credit_debt_id' => $creditDebt->id,
            'amount' => $validated['amount'],
            'paid_on' => $validated['paid_on'],
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (filled($validated['payment_method'] ?? null)) {
            $creditDebt->payment_method = $validated['payment_method'];
        }

        $this->refreshCreditDebtAmounts($creditDebt);
        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return back()->with('success', 'Payment recorded. Remaining ₹'.number_format($creditDebt->fresh()->remaining(), 2));
    }

    public function updatePayment(Request $request, CreditDebt $creditDebt, CreditDebtPayment $payment, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id || $payment->credit_debt_id !== $creditDebt->id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $otherPayments = (float) $creditDebt->payments()->whereKeyNot($payment->id)->sum('amount');
        if (($otherPayments + (float) $validated['amount']) - (float) $creditDebt->amount > 0.01) {
            return back()->withInput()->withErrors(['amount' => 'Payment total cannot exceed the original amount.']);
        }

        $payment->update($validated);

        if (filled($validated['payment_method'] ?? null)) {
            $creditDebt->payment_method = $validated['payment_method'];
        }

        $this->refreshCreditDebtAmounts($creditDebt);
        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return back()->with('success', 'Payment updated.');
    }

    public function deletePayment(Request $request, CreditDebt $creditDebt, CreditDebtPayment $payment, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id || $payment->credit_debt_id !== $creditDebt->id) {
            abort(403);
        }

        $payment->delete();
        $this->refreshCreditDebtAmounts($creditDebt);
        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return back()->with('success', 'Payment deleted.');
    }

    public function updateStatus(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['yet_to_pay', 'partially_paid', 'fully_paid', 'paid_late', 'failed_to_pay'])],
        ]);

        $creditDebt->status = $validated['status'];
        if ($validated['status'] === 'fully_paid') {
            $creditDebt->amount_paid = $creditDebt->amount;
            $creditDebt->fully_paid_at = Carbon::now();
        } else {
            $this->refreshCreditDebtAmounts($creditDebt, false);
            if (in_array($validated['status'], ['paid_late', 'failed_to_pay'], true)) {
                $creditDebt->status = $validated['status'];
                $creditDebt->save();
            }
        }

        $creditDebt->save();
        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return back()->with('success', 'Status updated to '.$creditDebt->statusLabel());
    }

    public function recordAsExpense(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $creditDebt->amount + 0.01)],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error = $this->alreadyExpensedError($creditDebt, (float) $validated['amount'])) {
            return back()->withInput()->with('error', $error);
        }

        $friendName = $creditDebt->friend?->name ?? 'Friend';
        $isDebt = $creditDebt->type === 'debt';
        $categoryName = $isDebt ? 'Loan / Lending' : 'Debt Repayment';

        $category = ExpenseCategory::firstOrCreate(
            ['name' => $categoryName, 'user_id' => null],
            ['icon' => 'hand-coins', 'color' => '#6366f1', 'is_archived' => false, 'is_voluntary' => false]
        );

        $desc = $isDebt
            ? 'Lent to '.$friendName.($creditDebt->description ? ' ('.$creditDebt->description.')' : '')
            : 'Credit Repayment to '.$friendName;

        DB::transaction(function () use ($request, $creditDebt, $validated, $category, $desc, $isDebt): void {
            $dailyRecord = DailyRecord::firstOrCreate([
                'user_id' => $request->user()->id,
                'record_date' => $validated['date'],
            ]);

            $expense = Expense::create([
                'user_id' => $request->user()->id,
                'credit_debt_id' => $creditDebt->id,
                'daily_record_id' => $dailyRecord->id,
                'category_id' => $category->id,
                'amount' => $validated['amount'],
                'gst_amount' => 0,
                'date' => $validated['date'],
                'time' => Carbon::now()->format('H:i'),
                'description' => $desc,
                'payment_method' => $validated['payment_method'],
                'paid_by' => 'Me',
                'paid_by_type' => 'me',
                'notes' => trim(($validated['notes'] ?? '')."\nLinked to ".ucfirst($creditDebt->type).' #'.$creditDebt->id),
                'is_voluntary' => false,
            ]);

            if (! $isDebt && $creditDebt->remaining() > 0) {
                CreditDebtPayment::create([
                    'credit_debt_id' => $creditDebt->id,
                    'expense_id' => $expense->id,
                    'amount' => min((float) $validated['amount'], (float) $creditDebt->remaining()),
                    'paid_on' => $validated['date'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => 'Filed as expense #'.$expense->id,
                ]);
            }

            $creditDebt->linked_expense_id = $expense->id;
            $creditDebt->payment_method = $validated['payment_method'];
            $this->refreshCreditDebtAmounts($creditDebt);
        });

        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return back()->with('success', ucfirst($creditDebt->type).' of ₹'.number_format($validated['amount'], 2).' filed as Normal Expense.');
    }

    public function recordAsPersonalExpense(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id || $creditDebt->type !== 'credit') {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $creditDebt->amount + 0.01)],
            'category_id' => ['nullable', 'exists:personal_expense_categories,id'],
            'classification' => ['nullable', 'in:necessary,discretionary,luxury'],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error = $this->alreadyExpensedError($creditDebt, (float) $validated['amount'])) {
            return back()->withInput()->with('error', $error);
        }

        $defaultCat = PersonalExpenseCategory::where(fn ($q) => $q->where('user_id', $request->user()->id)->orWhereNull('user_id'))->first();

        DB::transaction(function () use ($request, $creditDebt, $validated, $defaultCat): void {
            $personalExpense = PersonalExpense::create([
                'user_id' => $request->user()->id,
                'credit_debt_id' => $creditDebt->id,
                'category_id' => $validated['category_id'] ?? $defaultCat?->id,
                'classification' => $validated['classification'] ?? 'necessary',
                'amount' => $validated['amount'],
                'date' => $validated['date'],
                'time' => Carbon::now()->format('H:i'),
                'description' => 'Credit Repayment to '.($creditDebt->friend?->name ?? 'Friend'),
                'payment_method' => $validated['payment_method'],
                'done_by' => 'Me',
                'done_to' => $creditDebt->friend?->name ?? 'Friend',
                'notes' => trim(($validated['notes'] ?? '')."\nLinked to Credit #".$creditDebt->id),
                'is_voluntary' => false,
            ]);

            if ($creditDebt->remaining() > 0) {
                CreditDebtPayment::create([
                    'credit_debt_id' => $creditDebt->id,
                    'personal_expense_id' => $personalExpense->id,
                    'amount' => min((float) $validated['amount'], (float) $creditDebt->remaining()),
                    'paid_on' => $validated['date'],
                    'payment_method' => $validated['payment_method'],
                    'notes' => 'Filed as personal expense #'.$personalExpense->id,
                ]);
            }

            $creditDebt->linked_personal_expense_id = $personalExpense->id;
            $creditDebt->payment_method = $validated['payment_method'];
            $this->refreshCreditDebtAmounts($creditDebt);
        });

        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return back()->with('success', 'Credit repayment of ₹'.number_format($validated['amount'], 2).' filed as Personal Expense.');
    }

    public function recordAsIncome(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id || $creditDebt->type !== 'debt') {
            abort(403);
        }

        $remaining = $creditDebt->remaining();
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, $remaining + 0.01)],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $category = IncomeCategory::firstOrCreate(
            ['name' => 'Debt Recovery', 'user_id' => null],
            ['icon' => 'wallet', 'color' => '#10b981', 'is_archived' => false]
        );

        DB::transaction(function () use ($request, $creditDebt, $validated, $category): void {
            $dailyRecord = DailyRecord::firstOrCreate([
                'user_id' => $request->user()->id,
                'record_date' => $validated['date'],
            ]);

            $income = Income::create([
                'user_id' => $request->user()->id,
                'credit_debt_id' => $creditDebt->id,
                'daily_record_id' => $dailyRecord->id,
                'category_id' => $category->id,
                'amount' => $validated['amount'],
                'source' => 'Debt Collection: '.($creditDebt->friend?->name ?? 'Friend'),
                'date' => $validated['date'],
                'time' => Carbon::now()->format('H:i'),
                'payment_method' => $validated['payment_method'],
                'description' => 'Received from '.($creditDebt->friend?->name ?? 'Friend'),
                'notes' => trim(($validated['notes'] ?? '')."\nLinked to Debt #".$creditDebt->id),
            ]);

            CreditDebtPayment::create([
                'credit_debt_id' => $creditDebt->id,
                'income_id' => $income->id,
                'amount' => $validated['amount'],
                'paid_on' => $validated['date'],
                'payment_method' => $validated['payment_method'],
                'notes' => 'Recorded as Income #'.$income->id,
            ]);

            $creditDebt->linked_income_id = $income->id;
            $creditDebt->payment_method = $validated['payment_method'];
            $this->refreshCreditDebtAmounts($creditDebt);
        });

        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return back()->with('success', 'Debt recovery of ₹'.number_format($validated['amount'], 2).' recorded as Income.');
    }

    public function settleDiscounted(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'settled_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($creditDebt, $validated): void {
            $settledPaid = (float) $validated['settled_amount'];
            if ($settledPaid > 0) {
                CreditDebtPayment::create([
                    'credit_debt_id' => $creditDebt->id,
                    'amount' => $settledPaid,
                    'paid_on' => $validated['paid_on'],
                    'payment_method' => $validated['payment_method'] ?? null,
                    'notes' => 'Settlement final payment (Discount/Forgiven: ₹'.number_format((float) $validated['discount_amount'], 2).')',
                ]);
            }

            $creditDebt->settled_discount_amount = (float) $validated['discount_amount'];
            $creditDebt->is_settled_discounted = true;
            $creditDebt->amount_paid = (float) $creditDebt->payments()->sum('amount');
            $creditDebt->status = 'fully_paid';
            $creditDebt->fully_paid_at = Carbon::now();
            if (filled($validated['payment_method'] ?? null)) {
                $creditDebt->payment_method = $validated['payment_method'];
            }
            $ifNote = ! empty($validated['notes']) ? "\nSettlement Note: ".$validated['notes'] : '';
            $creditDebt->notes = trim(($creditDebt->notes ?? '').$ifNote);
            $creditDebt->save();
        });

        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return back()->with('success', 'Marked as settled with ₹'.number_format($validated['discount_amount'], 2).' forgiven/discounted.');
    }

    public function settleNoPay(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $remaining = $creditDebt->remaining();
        if ($remaining <= 0) {
            return back()->with('info', ucfirst($creditDebt->type).' is already fully settled / closed.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'settled_on' => ['nullable', 'date'],
        ]);

        $settleDate = $validated['settled_on'] ?? Carbon::today()->toDateString();
        $reason = filled($validated['reason'] ?? null)
            ? $validated['reason']
            : 'Waived off / Settled with no payment (₹0 closing)';

        DB::transaction(function () use ($creditDebt, $remaining, $reason): void {
            $creditDebt->settled_discount_amount = (float) $remaining;
            $creditDebt->is_settled_discounted = true;
            $creditDebt->status = 'fully_paid';
            $creditDebt->fully_paid_at = Carbon::now();
            $ifNote = "\nNo-Pay Settlement: ".$reason;
            $creditDebt->notes = trim(($creditDebt->notes ?? '').$ifNote);
            $creditDebt->save();
        });

        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        $typeLabel = ucfirst($creditDebt->type);

        return back()->with('success', "✅ {$typeLabel} marked as Settled with No Payment (₹".number_format($remaining, 2).' waived off / forgiven).');
    }

    public function close(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $remaining = $creditDebt->remaining();
        if ($remaining <= 0) {
            return back()->with('info', ucfirst($creditDebt->type).' is already fully settled / closed.');
        }

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $closeDate = $validated['date'] ?? Carbon::today()->toDateString();
        $closeMethod = filled($validated['payment_method'] ?? null)
            ? $validated['payment_method']
            : ($creditDebt->payment_method ?: 'Cash');
        $friendName = $creditDebt->friend?->name ?? 'Friend';

        // Only file what was not already filed earlier (e.g. "link as expense" when the credit
        // was created) — otherwise closing counted the same money twice.
        $amountToFile = $creditDebt->type === 'credit'
            ? min($remaining, $creditDebt->unexpensedAmount())
            : min($remaining, max(0, (float) $creditDebt->amount - (float) $creditDebt->settled_discount_amount - $creditDebt->incomeRecordedAmount()));
        $amountToFile = round($amountToFile, 2);

        DB::transaction(function () use ($request, $creditDebt, $remaining, $closeDate, $closeMethod, $friendName, $validated, $amountToFile): void {
            $dailyRecord = DailyRecord::firstOrCreate([
                'user_id' => $request->user()->id,
                'record_date' => $closeDate,
            ]);

            // 1. Record closing payment
            $closingPayment = CreditDebtPayment::create([
                'credit_debt_id' => $creditDebt->id,
                'amount' => $remaining,
                'paid_on' => $closeDate,
                'payment_method' => $closeMethod,
                'notes' => 'Closed in full',
            ]);

            // 2. Mark status fully paid
            $creditDebt->status = 'fully_paid';
            $creditDebt->amount_paid = (float) $creditDebt->amount;
            $creditDebt->payment_method = $closeMethod;
            $creditDebt->fully_paid_at = Carbon::now();
            if (! empty($validated['notes'])) {
                $creditDebt->notes = trim(($creditDebt->notes ?? '')."\nClosing note: ".$validated['notes']);
            }

            // 3. If Debt (Friend owed me) -> Friend paid me back -> Record as Income!
            if ($creditDebt->type === 'debt' && $amountToFile > 0) {
                $category = IncomeCategory::firstOrCreate(
                    ['name' => 'Debt Recovery', 'user_id' => null],
                    ['icon' => 'wallet', 'color' => '#10b981', 'is_archived' => false]
                );

                $income = Income::create([
                    'user_id' => $request->user()->id,
                    'credit_debt_id' => $creditDebt->id,
                    'daily_record_id' => $dailyRecord->id,
                    'category_id' => $category->id,
                    'amount' => $amountToFile,
                    'source' => 'Debt Collection: '.$friendName,
                    'date' => $closeDate,
                    'time' => Carbon::now()->format('H:i'),
                    'payment_method' => $closeMethod,
                    'description' => 'Received from '.$friendName.' (Debt #'.$creditDebt->id.' closed)',
                    'notes' => 'Debt #'.$creditDebt->id.' closed and settled in full.',
                ]);

                $creditDebt->linked_income_id = $income->id;
                if (abs($amountToFile - $remaining) < 0.01) {
                    $closingPayment->update(['income_id' => $income->id]);
                }
            }

            // 4. If Credit (I owed friend) -> I paid friend back -> Record as Normal Expense!
            if ($creditDebt->type === 'credit' && $amountToFile > 0) {
                $category = ExpenseCategory::firstOrCreate(
                    ['name' => 'Debt Repayment', 'user_id' => null],
                    ['icon' => 'hand-coins', 'color' => '#6366f1', 'is_archived' => false, 'is_voluntary' => false]
                );

                $expense = Expense::create([
                    'user_id' => $request->user()->id,
                    'credit_debt_id' => $creditDebt->id,
                    'daily_record_id' => $dailyRecord->id,
                    'category_id' => $category->id,
                    'amount' => $amountToFile,
                    'gst_amount' => 0,
                    'date' => $closeDate,
                    'time' => Carbon::now()->format('H:i'),
                    'description' => 'Credit Repaid to '.$friendName.' (Credit #'.$creditDebt->id.' closed)',
                    'payment_method' => $closeMethod,
                    'paid_by' => 'Me',
                    'paid_by_type' => 'me',
                    'notes' => 'Credit #'.$creditDebt->id.' closed and settled in full.',
                    'is_voluntary' => false,
                ]);

                $creditDebt->linked_expense_id = $expense->id;
                if (abs($amountToFile - $remaining) < 0.01) {
                    $closingPayment->update(['expense_id' => $expense->id]);
                }
            }

            $creditDebt->save();
        });

        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        $typeLabel = ucfirst($creditDebt->type);
        $recordKind = $creditDebt->type === 'debt' ? 'Income' : 'Normal Expense';
        $linkedMsg = $amountToFile > 0
            ? 'and ₹'.number_format($amountToFile, 2).' '.($creditDebt->type === 'debt' ? 'added as Income' : 'filed as Normal Expense')
            : '(already recorded as '.$recordKind.' earlier, so nothing new was added)';

        return back()->with('success', "✅ {$typeLabel} closed successfully {$linkedMsg}!");
    }

    /**
     * Error message when filing this amount as an expense would count the credit/debt twice.
     */
    private function alreadyExpensedError(CreditDebt $creditDebt, float $amount): ?string
    {
        $left = $creditDebt->unexpensedAmount();
        if ($amount - $left <= 0.01) {
            return null;
        }

        return '₹'.number_format($creditDebt->expensedAmount(), 2).' of this '.$creditDebt->type
            .' is already recorded as an expense; only ₹'.number_format($left, 2)
            .' can still be filed. Use "Add payment" to record a repayment without a new expense.';
    }

    private function refreshCreditDebtAmounts(CreditDebt $creditDebt, bool $allowManualOverride = true): void
    {
        $creditDebt->amount_paid = (float) $creditDebt->payments()->sum('amount');
        $creditDebt->syncStatusFromPayments($allowManualOverride);
        $creditDebt->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCreditDebt(Request $request, bool $allowInitialPaid): array
    {
        $rules = [
            'type' => ['required', Rule::in(['credit', 'debt'])],
            'friend_id' => ['required', 'exists:friends,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:150'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ];

        if ($allowInitialPaid) {
            $rules['amount_paid'] = ['nullable', 'numeric', 'min:0'];
        }

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request, $allowInitialPaid): void {
            if ($allowInitialPaid) {
                $amount = (float) $request->input('amount', 0);
                $paid = (float) $request->input('amount_paid', 0);
                if ($paid - $amount > 0.01) {
                    $validator->errors()->add('amount_paid', 'Paid amount (₹'.number_format($paid, 2).') cannot exceed the total amount (₹'.number_format($amount, 2).').');
                }
            }
        });

        return $validator->validate();
    }
}
