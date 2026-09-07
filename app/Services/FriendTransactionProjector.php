<?php

namespace App\Services;

use App\Models\CreditDebt;
use App\Models\Expense;
use App\Models\FriendSplit;
use App\Models\FriendTransaction;

class FriendTransactionProjector
{
    /**
     * @return array<string, mixed>
     */
    public function payloadForFriendSplit(FriendSplit $split): array
    {
        $netAmount = $split->netAmount();
        $type = 'shared_expense';
        if ((float) $split->my_share <= 0.0 && (float) $split->paid_by_me_amount > 0.0) {
            $type = 'paid_for_friend';
        } elseif ((float) $split->friend_share <= 0.0 && (float) $split->paid_by_friend_amount > 0.0) {
            $type = 'friend_paid_for_me';
        }

        return [
            'user_id' => $split->user_id,
            'friend_id' => $split->friend_id,
            'source_type' => 'friend_split',
            'source_id' => $split->id,
            'type' => $type,
            'paid_by_me' => (float) $split->paid_by_me_amount >= (float) $split->paid_by_friend_amount,
            'total_amount' => $split->total_amount,
            'my_share' => $split->my_share,
            'friend_share' => $split->friend_share,
            'description' => $split->description,
            'date' => $split->date,
            'payment_method' => $split->payment_method ?? 'Other',
            'projection_kind' => $split->expense_id ? 'expense_split' : 'standalone_split',
            'actual_paid_by_me' => $split->paid_by_me_amount,
            'actual_paid_by_friend' => $split->paid_by_friend_amount,
            'net_amount' => $netAmount,
            'is_settled' => false,
            'settled_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function payloadForCreditDebt(CreditDebt $item): ?array
    {
        $remaining = round((float) $item->remaining(), 2);
        if ($remaining <= 0) {
            return null;
        }

        $isDebt = $item->type === 'debt';

        return [
            'user_id' => $item->user_id,
            'friend_id' => $item->friend_id,
            'source_type' => 'credit_debt',
            'source_id' => $item->id,
            'type' => $isDebt ? 'paid_for_friend' : 'friend_paid_for_me',
            'paid_by_me' => $isDebt,
            'total_amount' => $item->amount,
            'my_share' => $isDebt ? 0 : $remaining,
            'friend_share' => $isDebt ? $remaining : 0,
            'description' => $item->description ?: ($isDebt ? 'Debt (they owe me)' : 'Credit (I owe)'),
            'date' => $item->date,
            'payment_method' => 'Other',
            'projection_kind' => $item->type,
            'actual_paid_by_me' => $isDebt ? $item->amount : 0,
            'actual_paid_by_friend' => $isDebt ? 0 : $item->amount,
            'net_amount' => $isDebt ? $remaining : -$remaining,
            'is_settled' => false,
            'settled_at' => null,
        ];
    }

    public function syncFriendSplit(FriendSplit $split): FriendTransaction
    {
        $transaction = FriendTransaction::updateOrCreate(
            [
                'source_type' => 'friend_split',
                'source_id' => $split->id,
            ],
            $this->payloadForFriendSplit($split)
        );

        if ($split->expense_id) {
            Expense::whereKey($split->expense_id)->update(['friend_transaction_id' => $transaction->id]);
        }

        return $transaction;
    }

    public function syncCreditDebt(CreditDebt $item): ?FriendTransaction
    {
        $payload = $this->payloadForCreditDebt($item);
        if ($payload === null) {
            $this->deleteBySource('credit_debt', $item->id);
            $item->friend_transaction_id = null;
            $item->saveQuietly();

            return null;
        }

        $transaction = FriendTransaction::updateOrCreate(
            [
                'source_type' => 'credit_debt',
                'source_id' => $item->id,
            ],
            $payload
        );

        $item->friend_transaction_id = $transaction->id;
        $item->saveQuietly();

        return $transaction;
    }

    public function deleteBySource(string $sourceType, int $sourceId): void
    {
        FriendTransaction::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }
}
