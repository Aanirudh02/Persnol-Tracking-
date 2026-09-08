<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\FriendSplit;
use App\Services\FinanceLinkService;
use App\Services\OptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FriendSplitController extends Controller
{
    public function store(Request $request, FinanceLinkService $linkService): RedirectResponse
    {
        $validated = $this->validateSplit($request);
        $friend = Friend::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['friend_id']);

        $linkService->createStandaloneSplit($friend, $validated);

        return redirect()->route('friends.index')->with('success', 'Split record created.');
    }

    public function edit(Request $request, FriendSplit $friendSplit, OptionsService $options): View
    {
        if ($friendSplit->user_id !== $request->user()->id) {
            abort(403);
        }

        $friends = Friend::query()->where('user_id', $request->user()->id)->orderBy('name')->get();
        $paymentMethods = $options->names('payment_method');

        return view('finance.friends.edit-split', compact('friendSplit', 'friends', 'paymentMethods'));
    }

    public function update(Request $request, FriendSplit $friendSplit, FinanceLinkService $linkService): RedirectResponse
    {
        if ($friendSplit->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $this->validateSplit($request, $friendSplit);
        $linkService->updateStandaloneSplit($friendSplit, $validated);

        return redirect()->route('friends.index')->with('success', 'Split record updated.');
    }

    public function destroy(Request $request, FriendSplit $friendSplit, FinanceLinkService $linkService): RedirectResponse
    {
        if ($friendSplit->user_id !== $request->user()->id) {
            abort(403);
        }

        $linkService->deleteStandaloneSplit($friendSplit);

        return redirect()->route('friends.index')->with('success', 'Split record deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSplit(Request $request, ?FriendSplit $friendSplit = null): array
    {
        $validator = Validator::make($request->all(), [
            'friend_id' => ['required', 'integer', Rule::exists('friends', 'id')],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'my_share' => ['required', 'numeric', 'min:0'],
            'friend_share' => ['required', 'numeric', 'min:0'],
            'paid_by_mode' => ['required', Rule::in(['me', 'friend', 'split'])],
            'paid_by_me_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_by_friend_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $validator->after(function ($validator) use ($request, $friendSplit): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $total = round((float) $request->input('total_amount'), 2);
            $myShare = round((float) $request->input('my_share'), 2);
            $friendShare = round((float) $request->input('friend_share'), 2);

            if (abs(($myShare + $friendShare) - $total) > 0.01) {
                $validator->errors()->add('my_share', 'Shares must add up to the total amount.');
            }

            $friendId = (int) $request->input('friend_id');
            $friendExists = Friend::query()
                ->where('user_id', $request->user()->id)
                ->whereKey($friendId)
                ->exists();
            if (! $friendExists) {
                $validator->errors()->add('friend_id', 'Choose one of your own friends.');
            }

            if ($friendSplit?->expense_id) {
                $validator->errors()->add('friend_id', 'Expense-linked split records are edited from the expense itself.');
            }

            $paidByMe = match ($request->input('paid_by_mode', 'me')) {
                'friend' => 0.0,
                'split' => round((float) ($request->input('paid_by_me_amount') ?? 0), 2),
                default => $total,
            };
            if ($request->input('paid_by_mode') === 'split') {
                $enteredFriendPaid = round((float) ($request->input('paid_by_friend_amount') ?? 0), 2);
                if (abs(($paidByMe + $enteredFriendPaid) - $total) > 0.01) {
                    $validator->errors()->add('paid_by_me_amount', 'Actual paid amounts must add up to the total amount.');
                }
            }
        });

        $validated = $validator->validate();
        $validated['paid_by_me_amount'] = $this->resolvePaidByMeAmount($validated);
        $validated['paid_by_friend_amount'] = round((float) $validated['total_amount'] - (float) $validated['paid_by_me_amount'], 2);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolvePaidByMeAmount(array $validated): float
    {
        $total = round((float) $validated['total_amount'], 2);

        return match ($validated['paid_by_mode']) {
            'friend' => 0,
            'split' => round((float) ($validated['paid_by_me_amount'] ?? 0), 2),
            default => $total,
        };
    }
}
