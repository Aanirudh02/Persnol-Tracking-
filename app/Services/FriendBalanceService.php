<?php

namespace App\Services;

use App\Models\Friend;
use App\Models\FriendSplit;
use Illuminate\Support\Collection;

class FriendBalanceService
{
    /**
     * @return array{friend_owes_me: float, i_owe_friend: float, net: float}
     */
    public function forFriend(Friend $friend): array
    {
        $friend->loadMissing(['friendSplits', 'creditDebts.payments', 'settlements']);

        $splitNet = (float) $friend->friendSplits
            ->sum(fn (FriendSplit $split): float => $split->netAmount());

        $creditDebtNet = (float) $friend->creditDebts
            ->sum(function ($item): float {
                $remaining = (float) $item->remaining();

                return $item->type === 'debt' ? $remaining : -$remaining;
            });

        $settlementNet = (float) $friend->settlements
            ->sum(fn ($settlement): float => $settlement->direction === 'i_paid_friend'
                ? (float) $settlement->amount
                : -((float) $settlement->amount));

        $net = round($splitNet + $creditDebtNet + $settlementNet, 2);

        return [
            'friend_owes_me' => max(0, $net),
            'i_owe_friend' => max(0, abs(min(0, $net))),
            'net' => $net,
        ];
    }

    /**
     * @param  Collection<int, Friend>  $friends
     * @return array<int, array{friend: Friend, balance: array{friend_owes_me: float, i_owe_friend: float, net: float}}>
     */
    public function summarize(Collection $friends): array
    {
        return $friends->map(function (Friend $friend): array {
            return [
                'friend' => $friend,
                'balance' => $this->forFriend($friend),
            ];
        })->all();
    }
}
