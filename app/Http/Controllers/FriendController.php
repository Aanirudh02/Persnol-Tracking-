<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Friend;
use App\Models\FriendTransaction;
use App\Services\FinanceService;
use Carbon\Carbon;

class FriendController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $friends = Friend::where('user_id', $user->id)->with(['transactions', 'settlements'])->get();

        $friendData = [];
        $totalOwedToMe = 0;
        $totalIOwe = 0;

        foreach ($friends as $f) {
            $bal = $f->getBalance();
            $openCreditsDebts = $f->creditDebts()
                ->whereNotIn('status', ['fully_paid'])
                ->orderByDesc('date')
                ->get();

            $friendData[] = [
                'friend' => $f,
                'balance' => $bal,
                'open_items' => $openCreditsDebts,
                'recent_transactions' => $f->transactions()->orderByDesc('date')->take(5)->get(),
            ];
            if ($bal['net'] > 0) {
                $totalOwedToMe += $bal['net'];
            } else {
                $totalIOwe += abs($bal['net']);
            }
        }

        return view('finance.friends.index', compact('friendData', 'totalOwedToMe', 'totalIOwe'));
    }

    public function storeFriend(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:100',
            'user_type' => 'required|in:Friend,Not a Friend',
            'date_of_birth' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        Friend::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'role' => $validated['user_type'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $label = $validated['user_type'] === 'Friend' ? 'Friend' : 'User';

        return redirect()->route('friends.index')->with('success', $label.' added successfully!');
    }

    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'friend_id' => 'required|exists:friends,id',
            'type' => 'required|in:paid_for_friend,friend_paid_for_me,shared_expense',
            'total_amount' => 'required|numeric|min:0.01',
            'my_share' => 'required|numeric|min:0',
            'friend_share' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $friend = Friend::where('user_id', $request->user()->id)->where('id', $validated['friend_id'])->firstOrFail();

        FriendTransaction::create([
            'user_id' => $request->user()->id,
            'friend_id' => $friend->id,
            'type' => $validated['type'],
            'paid_by_me' => $validated['type'] !== 'friend_paid_for_me',
            'total_amount' => $validated['total_amount'],
            'my_share' => $validated['my_share'],
            'friend_share' => $validated['friend_share'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'payment_method' => $validated['payment_method'],
        ]);

        return redirect()->route('friends.index')->with('success', 'Friend transaction recorded successfully!');
    }

    public function settle(Request $request, Friend $friend, FinanceService $financeService)
    {
        if ($friend->user_id !== auth()->id()) abort(403);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'direction' => 'required|in:i_paid_friend,friend_paid_me',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $financeService->settleWithFriend(
            friend: $friend,
            amount: $validated['amount'],
            direction: $validated['direction'],
            paymentMethod: $validated['payment_method'],
            notes: $validated['notes'] ?? null
        );

        return redirect()->route('friends.index')->with('success', "Settlement recorded for {$friend->name}!");
    }
}
