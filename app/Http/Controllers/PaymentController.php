<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\AuditService;
use App\Services\FinanceService;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::where('user_id', $user->id)->with('reconciliation');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('paid_to')) {
            $query->where('paid_to', 'like', "%{$request->paid_to}%");
        }

        $payments = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $totalPending = Payment::where('user_id', $user->id)->where('status', 'Pending')->sum('amount');
        $totalReconciled = Payment::where('user_id', $user->id)->where('status', 'Reconciled')->sum('amount');

        return view('finance.payments.index', compact('payments', 'totalPending', 'totalReconciled'));
    }

    public function create(OptionsService $options)
    {
        $paymentMethods = $options->names('payment_method');

        return view('finance.payments.create', compact('paymentMethods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'time' => 'nullable',
            'paid_by' => 'nullable|string|max:100',
            'paid_to' => 'required|string|max:100',
            'purpose' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string|max:100',
            'status' => 'required|in:Pending,Reconciled,Cancelled,Disputed',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
            'paid_by' => $validated['paid_by'] ?? 'Me',
            'paid_to' => $validated['paid_to'],
            'purpose' => $validated['purpose'],
            'category' => $validated['category'] ?? 'Other',
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditService::log('payment', $payment->id, 'created', null, $payment->toArray(), 'Payment created');

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully!');
    }

    public function reconcile(Request $request, Payment $payment, FinanceService $financeService)
    {
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'reconciled_date' => 'required|date',
            'reconciled_amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        $financeService->reconcilePayment($payment, $validated);

        return redirect()->route('payments.index')->with('success', 'Payment reconciled successfully!');
    }

    public function edit(Payment $payment, FinanceService $financeService, OptionsService $options)
    {
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('payment', $payment)) {
            return redirect()->route('payments.index')->with('error', '🔒 This payment is locked from editing.');
        }

        $paymentMethods = $options->names('payment_method');

        return view('finance.payments.edit', compact('payment', 'paymentMethods'));
    }

    public function update(Request $request, Payment $payment, FinanceService $financeService)
    {
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('payment', $payment)) {
            return redirect()->route('payments.index')->with('error', '🔒 This payment is locked from editing.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'time' => 'nullable',
            'paid_by' => 'nullable|string|max:100',
            'paid_to' => 'required|string|max:100',
            'purpose' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'payment_method' => 'required|string',
            'reference' => 'nullable|string|max:100',
            'status' => 'required|in:Pending,Reconciled,Cancelled,Disputed',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldValues = $payment->only(['amount', 'paid_to', 'status', 'purpose']);
        $payment->update($validated);

        AuditService::log(
            module: 'payment',
            recordId: $payment->id,
            action: 'updated',
            oldValues: $oldValues,
            newValues: $payment->only(['amount', 'paid_to', 'status', 'purpose']),
            reason: $request->input('reason', 'Payment edited by user')
        );

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully!');
    }

    public function destroy(Request $request, Payment $payment)
    {
        if ($payment->user_id !== auth()->id()) {
            abort(403);
        }

        AuditService::log('payment', $payment->id, 'deleted', $payment->toArray(), null, $request->input('reason', 'Payment deleted'));
        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted.');
    }
}
