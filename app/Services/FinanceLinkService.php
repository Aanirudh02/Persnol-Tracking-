<?php

namespace App\Services;

use App\Models\CreditDebt;
use App\Models\Expense;
use App\Models\Friend;
use App\Models\FriendSplit;
use App\Models\FriendTransaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FinanceLinkService
{
    public function __construct(
        private readonly FriendTransactionProjector $projector
    ) {}

    /**
     * @param  array<string, mixed>|null  $splitData
     */
    public function syncExpenseFriendLink(Expense $expense, ?array $splitData = null): ?FriendTransaction
    {
        return DB::transaction(function () use ($expense, $splitData): ?FriendTransaction {
            $payload = $this->expenseSplitPayload($expense, $splitData);

            if ($payload === null) {
                $this->removeExpenseFriendLink($expense);

                return null;
            }

            $split = FriendSplit::query()->updateOrCreate(
                ['expense_id' => $expense->id],
                $payload
            );

            $transaction = $this->projector->syncFriendSplit($split);
            $expense->friend_transaction_id = $transaction->id;
            $expense->saveQuietly();

            return $transaction;
        });
    }

    public function removeExpenseFriendLink(Expense $expense): void
    {
        DB::transaction(function () use ($expense): void {
            $split = FriendSplit::query()->where('expense_id', $expense->id)->first();
            if ($split) {
                $this->projector->deleteBySource('friend_split', $split->id);
                $split->delete();
            }

            if ($expense->friend_transaction_id) {
                FriendTransaction::query()->whereKey($expense->friend_transaction_id)->delete();
            }

            $expense->friend_transaction_id = null;
            $expense->saveQuietly();
        });
    }

    public function syncCreditDebtToFriend(CreditDebt $item): ?FriendTransaction
    {
        return DB::transaction(fn (): ?FriendTransaction => $this->projector->syncCreditDebt($item));
    }

    public function removeCreditDebtLink(CreditDebt $item): void
    {
        DB::transaction(function () use ($item): void {
            $this->projector->deleteBySource('credit_debt', $item->id);
            $item->friend_transaction_id = null;
            $item->saveQuietly();
        });
    }

    /**
     * @param  array<string, mixed>|null  $splitData
     * @return array<string, mixed>|null
     */
    public function expenseSplitPayload(Expense $expense, ?array $splitData = null): ?array
    {
        $friendId = (int) ($splitData['friend_id'] ?? $expense->split_with_friend_id ?? $expense->paid_by_friend_id ?? 0);
        if ($friendId <= 0) {
            return null;
        }

        $total = round((float) $expense->totalAmount(), 2);
        $myShare = round((float) ($splitData['my_share'] ?? $expense->split_my_share ?? $total), 2);
        $friendShare = round((float) ($splitData['friend_share'] ?? $expense->split_friend_share ?? max(0, $total - $myShare)), 2);
        $paidByMode = (string) ($splitData['paid_by_mode'] ?? $this->legacyPaidByMode($expense));

        $paidByMe = round((float) ($splitData['paid_by_me_amount'] ?? $this->defaultPaidByMeAmount($paidByMode, $total)), 2);
        $paidByFriend = round((float) ($splitData['paid_by_friend_amount'] ?? $this->defaultPaidByFriendAmount($paidByMode, $total)), 2);

        return [
            'user_id' => $expense->user_id,
            'friend_id' => $friendId,
            'description' => $expense->description,
            'date' => $expense->date,
            'payment_method' => $expense->payment_method,
            'total_amount' => $total,
            'my_share' => $myShare,
            'friend_share' => $friendShare,
            'paid_by_me_amount' => $paidByMe,
            'paid_by_friend_amount' => $paidByFriend,
            'notes' => Arr::get($splitData, 'notes', $expense->notes),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createStandaloneSplit(Friend $friend, array $data): FriendSplit
    {
        return DB::transaction(function () use ($friend, $data): FriendSplit {
            $split = FriendSplit::query()->create([
                'user_id' => $friend->user_id,
                'friend_id' => $friend->id,
                'description' => $data['description'],
                'date' => $data['date'],
                'payment_method' => $data['payment_method'] ?? null,
                'total_amount' => $data['total_amount'],
                'my_share' => $data['my_share'],
                'friend_share' => $data['friend_share'],
                'paid_by_me_amount' => $data['paid_by_me_amount'],
                'paid_by_friend_amount' => $data['paid_by_friend_amount'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->projector->syncFriendSplit($split);

            return $split;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStandaloneSplit(FriendSplit $split, array $data): FriendSplit
    {
        return DB::transaction(function () use ($split, $data): FriendSplit {
            $split->update([
                'friend_id' => $data['friend_id'],
                'description' => $data['description'],
                'date' => $data['date'],
                'payment_method' => $data['payment_method'] ?? null,
                'total_amount' => $data['total_amount'],
                'my_share' => $data['my_share'],
                'friend_share' => $data['friend_share'],
                'paid_by_me_amount' => $data['paid_by_me_amount'],
                'paid_by_friend_amount' => $data['paid_by_friend_amount'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->projector->syncFriendSplit($split->fresh());

            return $split->fresh();
        });
    }

    public function deleteStandaloneSplit(FriendSplit $split): void
    {
        DB::transaction(function () use ($split): void {
            $this->projector->deleteBySource('friend_split', $split->id);
            $split->delete();
        });
    }

    private function legacyPaidByMode(Expense $expense): string
    {
        return match ($expense->paid_by_type) {
            'friend' => 'friend',
            'split' => 'split',
            default => 'me',
        };
    }

    private function defaultPaidByMeAmount(string $paidByMode, float $total): float
    {
        return match ($paidByMode) {
            'friend' => 0,
            'split' => 0,
            default => $total,
        };
    }

    private function defaultPaidByFriendAmount(string $paidByMode, float $total): float
    {
        return match ($paidByMode) {
            'friend' => $total,
            'split' => $total,
            default => 0,
        };
    }
}
