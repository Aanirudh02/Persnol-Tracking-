<?php

namespace App\Http\Controllers;

use App\Models\CreditDebt;
use App\Models\CreditDebtPayment;
use App\Models\Friend;
use App\Services\FinanceLinkService;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CreditDebtController extends Controller
{
    public function index(Request $request, OptionsService $options)
    {
        $type = $request->get('type', 'credit');
        if (! in_array($type, ['credit', 'debt'], true)) {
            $type = 'credit';
        }

        $items = CreditDebt::where('user_id', $request->user()->id)
            ->where('type', $type)
            ->with(['friend', 'payments'])
            ->orderByDesc('date')
            ->paginate(20)
            ->withQueryString();

        $friends = Friend::where('user_id', $request->user()->id)->orderBy('name')->get();
        $openTotal = (float) CreditDebt::where('user_id', $request->user()->id)
            ->where('type', $type)
            ->whereNotIn('status', ['fully_paid'])
            ->get()
            ->sum(fn ($i) => $i->remaining());

        $statuses = $options->for('credit_status');
        $paymentMethods = $options->names('payment_method');

        return view('finance.credits.index', compact('items', 'friends', 'type', 'openTotal', 'statuses', 'paymentMethods'));
    }

    public function store(Request $request, FinanceLinkService $linkService)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit,debt',
            'friend_id' => 'required|exists:friends,id',
            'amount' => 'required|numeric|min:0.01',
            'amount_paid' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'location' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|max:50',
        ]);

        $friend = Friend::where('user_id', $request->user()->id)->findOrFail($validated['friend_id']);

        $item = CreditDebt::create([
            'user_id' => $request->user()->id,
            'friend_id' => $friend->id,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'amount_paid' => $validated['amount_paid'] ?? 0,
            'date' => $validated['date'],
            'location' => $validated['location'] ?? null,
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'source' => 'manual',
            'status' => $validated['status'] ?? 'yet_to_pay',
        ]);

        if (! empty($validated['status']) && in_array($validated['status'], ['paid_late', 'failed_to_pay'], true)) {
            // keep manual status
        } else {
            $item->syncStatusFromPayments(false);
        }
        $item->save();

        $linkService->syncCreditDebtToFriend($item);

        return back()->with('success', ucfirst($item->type).' recorded.');
    }

    public function addPayment(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService)
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'paid_on' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        CreditDebtPayment::create([
            'credit_debt_id' => $creditDebt->id,
            'amount' => $validated['amount'],
            'paid_on' => $validated['paid_on'],
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $creditDebt->amount_paid = (float) $creditDebt->amount_paid + (float) $validated['amount'];
        $creditDebt->syncStatusFromPayments(false);
        $creditDebt->save();

        $linkService->syncCreditDebtToFriend($creditDebt);

        return back()->with('success', 'Payment recorded. Remaining ₹'.number_format($creditDebt->remaining(), 2));
    }

    public function updateStatus(Request $request, CreditDebt $creditDebt, FinanceLinkService $linkService)
    {
        if ($creditDebt->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:yet_to_pay,partially_paid,fully_paid,paid_late,failed_to_pay',
        ]);

        $creditDebt->status = $validated['status'];
        if ($validated['status'] === 'fully_paid') {
            $creditDebt->amount_paid = $creditDebt->amount;
            $creditDebt->fully_paid_at = Carbon::now();
        }
        $creditDebt->save();
        $linkService->syncCreditDebtToFriend($creditDebt);

        return back()->with('success', 'Status updated to '.$creditDebt->statusLabel());
    }
}
