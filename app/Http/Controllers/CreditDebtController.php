<?php

namespace App\Http\Controllers;

use App\Models\CreditDebt;
use App\Models\CreditDebtPayment;
use App\Models\Friend;
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
            ->with(['friend', 'payments'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $friends = Friend::query()->where('user_id', $request->user()->id)->orderBy('name')->get();
        $openTotal = (float) CreditDebt::query()
            ->where('user_id', $request->user()->id)
            ->where('type', $type)
            ->whereNotIn('status', ['fully_paid'])
            ->get()
            ->sum(fn ($item) => $item->remaining());

        $statuses = $options->for('credit_status');
        $paymentMethods = $options->names('payment_method');

        return view('finance.credits.index', compact('items', 'friends', 'type', 'openTotal', 'statuses', 'paymentMethods'));
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

            return $item->fresh();
        });

        $linkService->syncCreditDebtToFriend($item);

        return back()->with('success', ucfirst($item->type).' recorded.');
    }

    public function edit(Request $request, CreditDebt $creditDebt, OptionsService $options): View
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $friends = Friend::query()->where('user_id', $request->user()->id)->orderBy('name')->get();
        $statuses = $options->for('credit_status');
        $paymentMethods = $options->names('payment_method');
        $creditDebt->load(['payments' => fn ($query) => $query->orderByDesc('paid_on')->orderByDesc('created_at')]);

        return view('finance.credits.edit', compact('creditDebt', 'friends', 'statuses', 'paymentMethods'));
    }

    public function update(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $this->validateCreditDebt($request, true);
        $friend = Friend::query()->where('user_id', $request->user()->id)->findOrFail($validated['friend_id']);

        DB::transaction(function () use ($creditDebt, $validated, $friend): void {
            $creditDebt->update([
                'friend_id' => $friend->id,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'date' => $validated['date'],
                'location' => $validated['location'] ?? null,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'status' => $validated['status'] ?? $creditDebt->status,
            ]);

            if (array_key_exists('amount_paid', $validated)) {
                $targetPaid = round((float) $validated['amount_paid'], 2);
                $method = filled($validated['payment_method'] ?? null) ? $validated['payment_method'] : null;
                $existingPaymentsCount = $creditDebt->payments()->count();

                if ($existingPaymentsCount === 0) {
                    if ($targetPaid > 0) {
                        CreditDebtPayment::create([
                            'credit_debt_id' => $creditDebt->id,
                            'amount' => $targetPaid,
                            'paid_on' => $validated['date'],
                            'payment_method' => $method,
                            'notes' => 'Direct paid update',
                        ]);
                    }
                } elseif ($existingPaymentsCount === 1) {
                    $singlePayment = $creditDebt->payments()->first();
                    if ($targetPaid <= 0) {
                        $singlePayment->delete();
                    } else {
                        $singlePayment->update([
                            'amount' => $targetPaid,
                            'payment_method' => $method ?: $singlePayment->payment_method,
                        ]);
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
                                'payment_method' => $method,
                                'notes' => 'Adjusted paid amount',
                            ]);
                        }
                    }
                }
            }

            $this->refreshCreditDebtAmounts($creditDebt);
        });

        $linkService->syncCreditDebtToFriend($creditDebt->fresh());

        return redirect()->route('credits.index', ['type' => $creditDebt->type])->with('success', ucfirst($creditDebt->type).' updated.');
    }

    public function destroy(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService): RedirectResponse
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $type = $creditDebt->type;
        $linkService->removeCreditDebtLink($creditDebt);
        $creditDebt->payments()->delete();
        $creditDebt->delete();

        return redirect()->route('credits.index', ['type' => $type])->with('success', ucfirst($type).' deleted.');
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
