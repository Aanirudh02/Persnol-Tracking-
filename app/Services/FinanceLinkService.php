<?php

namespace App\Services;

use App\Models\CreditDebt;
use App\Models\Expense;
use App\Models\FriendTransaction;
use Carbon\Carbon;

class FinanceLinkService
{
    public function syncExpenseFriendLink(Expense $expense): ?FriendTransaction
    {
        if ($expense->friend_transaction_id) {
            $tx = FriendTransaction::find($expense->friend_transaction_id);
            if ($tx) {
                $this->applyExpenseToTransaction($expense, $tx);
                $tx->save();

                return $tx;
            }
        }

        if ($expense->paid_by_type === 'friend' && $expense->paid_by_friend_id) {
            $tx = FriendTransaction::create([
                'user_id' => $expense->user_id,
                'friend_id' => $expense->paid_by_friend_id,
                'type' => 'friend_paid_for_me',
                'paid_by_me' => false,
                'total_amount' => $expense->totalAmount(),
                'my_share' => $expense->totalAmount(),
                'friend_share' => 0,
                'description' => $expense->description,
                'date' => $expense->date,
                'payment_method' => $expense->payment_method,
                'is_settled' => false,
            ]);
            $expense->friend_transaction_id = $tx->id;
            $expense->saveQuietly();

            return $tx;
        }

        if ($expense->split_with_friend_id) {
            $tx = FriendTransaction::create([
                'user_id' => $expense->user_id,
                'friend_id' => $expense->split_with_friend_id,
                'type' => 'shared_expense',
                'paid_by_me' => ($expense->paid_by_type ?? 'me') === 'me',
                'total_amount' => $expense->totalAmount(),
                'my_share' => $expense->split_my_share ?? round($expense->totalAmount() / 2, 2),
                'friend_share' => $expense->split_friend_share ?? round($expense->totalAmount() / 2, 2),
                'description' => $expense->description,
                'date' => $expense->date,
                'payment_method' => $expense->payment_method,
                'is_settled' => false,
            ]);
            $expense->friend_transaction_id = $tx->id;
            $expense->saveQuietly();

            return $tx;
        }

        return null;
    }

    public function removeExpenseFriendLink(Expense $expense): void
    {
        if ($expense->friend_transaction_id) {
            FriendTransaction::where('id', $expense->friend_transaction_id)->delete();
            $expense->friend_transaction_id = null;
            $expense->saveQuietly();
        }
    }

    protected function applyExpenseToTransaction(Expense $expense, FriendTransaction $tx): void
    {
        if ($expense->paid_by_type === 'friend' && $expense->paid_by_friend_id) {
            $tx->friend_id = $expense->paid_by_friend_id;
            $tx->type = 'friend_paid_for_me';
            $tx->paid_by_me = false;
            $tx->total_amount = $expense->totalAmount();
            $tx->my_share = $expense->totalAmount();
            $tx->friend_share = 0;
        } elseif ($expense->split_with_friend_id) {
            $tx->friend_id = $expense->split_with_friend_id;
            $tx->type = 'shared_expense';
            $tx->paid_by_me = ($expense->paid_by_type ?? 'me') === 'me';
            $tx->total_amount = $expense->totalAmount();
            $tx->my_share = $expense->split_my_share ?? round($expense->totalAmount() / 2, 2);
            $tx->friend_share = $expense->split_friend_share ?? round($expense->totalAmount() / 2, 2);
        }

        $tx->description = $expense->description;
        $tx->date = $expense->date;
        $tx->payment_method = $expense->payment_method;
    }

    public function syncCreditDebtToFriend(CreditDebt $item): ?FriendTransaction
    {
        $remaining = $item->remaining();
        if ($remaining <= 0) {
            if ($item->friend_transaction_id) {
                FriendTransaction::where('id', $item->friend_transaction_id)->update([
                    'is_settled' => true,
                    'settled_at' => Carbon::now(),
                ]);
            }

            return null;
        }

        // New rule: Credit = I owe them; Debt = they owe me
        $type = $item->type === 'credit' ? 'friend_paid_for_me' : 'paid_for_friend';
        $payload = [
            'user_id' => $item->user_id,
            'friend_id' => $item->friend_id,
            'type' => $type,
            'paid_by_me' => $item->type === 'debt',
            'total_amount' => $item->amount,
            'my_share' => $item->type === 'credit' ? $remaining : 0,
            'friend_share' => $item->type === 'debt' ? $remaining : 0,
            'description' => $item->description ?: ($item->type === 'credit' ? 'Credit (I owe)' : 'Debt (they owe me)'),
            'date' => $item->date,
            'payment_method' => 'Other',
            'is_settled' => false,
        ];

        if ($item->friend_transaction_id) {
            $tx = FriendTransaction::find($item->friend_transaction_id);
            if ($tx) {
                $tx->update($payload);

                return $tx;
            }
        }

        $tx = FriendTransaction::create($payload);
        $item->friend_transaction_id = $tx->id;
        $item->saveQuietly();

        return $tx;
    }
}
