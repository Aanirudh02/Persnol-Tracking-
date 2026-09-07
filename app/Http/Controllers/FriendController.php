<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Services\FinanceLinkService;
use App\Services\FinanceService;
use App\Services\FriendBalanceService;
use App\Services\OptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FriendController extends Controller
{
    public function index(Request $request, FriendBalanceService $balanceService, OptionsService $options): View
    {
        $user = $request->user();
        $friends = Friend::query()
            ->where('user_id', $user->id)
            ->with([
                'friendSplits' => fn ($query) => $query->orderByDesc('date')->orderByDesc('created_at'),
                'creditDebts.payments',
                'settlements' => fn ($query) => $query->orderByDesc('date')->orderByDesc('created_at'),
            ])
            ->orderBy('name')
            ->get();

        $friendData = [];
        $totalOwedToMe = 0;
        $totalIOwe = 0;

        foreach ($friends as $friend) {
            $balance = $balanceService->forFriend($friend);
            $friendData[] = [
                'friend' => $friend,
                'balance' => $balance,
                'split_records' => $friend->friendSplits->take(5),
                'open_items' => $friend->creditDebts
                    ->filter(fn ($item) => $item->remaining() > 0)
                    ->sortByDesc('date')
                    ->values(),
                'recent_settlements' => $friend->settlements->take(5),
            ];

            if ($balance['net'] > 0) {
                $totalOwedToMe += $balance['net'];
            } elseif ($balance['net'] < 0) {
                $totalIOwe += abs($balance['net']);
            }
        }

        $paymentMethods = $options->names('payment_method');

        return view('finance.friends.index', compact('friendData', 'totalOwedToMe', 'totalIOwe', 'paymentMethods'));
    }

    public function storeFriend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'user_type' => ['required', Rule::in(['Friend', 'Not a Friend'])],
            'date_of_birth' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        Friend::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'role' => $validated['user_type'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $label = $validated['user_type'] === 'Friend' ? 'Friend' : 'User';

        return redirect()->route('friends.index')->with('success', $label.' added successfully!');
    }

    public function storeTransaction(Request $request, FinanceLinkService $linkService): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => ['required', 'integer', Rule::exists('friends', 'id')],
            'type' => ['required', Rule::in(['paid_for_friend', 'friend_paid_for_me', 'shared_expense'])],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'my_share' => ['required', 'numeric', 'min:0'],
            'friend_share' => ['required', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $friendExists = Friend::query()
                ->where('user_id', $request->user()->id)
                ->whereKey((int) $request->input('friend_id'))
                ->exists();

            if (! $friendExists) {
                $validator->errors()->add('friend_id', 'Choose one of your own friends.');
            }

            $total = round((float) $request->input('total_amount'), 2);
            $myShare = round((float) $request->input('my_share'), 2);
            $friendShare = round((float) $request->input('friend_share'), 2);

            if (abs(($myShare + $friendShare) - $total) > 0.01) {
                $validator->errors()->add('my_share', 'Shares must add up to the total amount.');
            }
        });

        $validated = $validator->validate();
        $friend = Friend::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['friend_id']);

        $paidByMeAmount = match ($validated['type']) {
            'friend_paid_for_me' => 0,
            default => (float) $validated['total_amount'],
        };

        $linkService->createStandaloneSplit($friend, [
            'description' => $validated['description'],
            'date' => $validated['date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'total_amount' => $validated['total_amount'],
            'my_share' => $validated['my_share'],
            'friend_share' => $validated['friend_share'],
            'paid_by_me_amount' => $paidByMeAmount,
            'paid_by_friend_amount' => round((float) $validated['total_amount'] - $paidByMeAmount, 2),
        ]);

        return redirect()->route('friends.index')->with('success', 'Friend transaction recorded successfully!');
    }

    public function settle(Request $request, Friend $friend, FinanceService $financeService, FriendBalanceService $balanceService): RedirectResponse
    {
        if ($friend->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'direction' => ['required', Rule::in(['i_paid_friend', 'friend_paid_me'])],
            'payment_method' => 'required|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $balance = $balanceService->forFriend($friend);
        $openAmount = $validated['direction'] === 'i_paid_friend'
            ? $balance['i_owe_friend']
            : $balance['friend_owes_me'];

        if ((float) $validated['amount'] - $openAmount > 0.01) {
            return back()->withInput()->withErrors([
                'amount' => 'Settlement amount cannot exceed the current open balance.',
            ]);
        }

        $financeService->settleWithFriend(
            friend: $friend,
            amount: (float) $validated['amount'],
            direction: $validated['direction'],
            paymentMethod: $validated['payment_method'],
            notes: $validated['notes'] ?? null
        );

        return redirect()->route('friends.index')->with('success', "Settlement recorded for {$friend->name}!");
    }
}
