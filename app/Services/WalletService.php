<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\PaymentWallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        private readonly OptionsService $options
    ) {}

    public function enabledWallets(int $userId): Collection
    {
        $this->ensureDefaults($userId);

        return PaymentWallet::query()
            ->where('user_id', $userId)
            ->where('is_enabled', true)
            ->orderBy('payment_method')
            ->get()
            ->map(function (PaymentWallet $wallet) use ($userId) {
                $wallet->current_balance = $this->balanceFor($userId, $wallet);

                return $wallet;
            });
    }

    public function displayWallets(int $userId): Collection
    {
        $this->ensureDefaults($userId);
        $order = $this->options->names('payment_method', $userId);

        return PaymentWallet::query()
            ->where('user_id', $userId)
            ->get()
            ->sortBy(function (PaymentWallet $wallet) use ($order): int {
                $index = array_search($wallet->payment_method, $order, true);

                return $index === false ? 999 : $index;
            })
            ->values()
            ->map(function (PaymentWallet $wallet) use ($userId): PaymentWallet {
                $wallet->current_balance = $this->balanceFor($userId, $wallet);

                return $wallet;
            });
    }

    public function balanceFor(int $userId, PaymentWallet $wallet): float
    {
        $asOf = $wallet->opening_as_of?->toDateString() ?? '1970-01-01';
        $method = $wallet->payment_method;

        $income = (float) Income::query()
            ->where('incomes.user_id', $userId)
            ->where('incomes.payment_method', $method)
            ->where('incomes.date', '>=', $asOf)
            ->leftJoin('income_categories', 'incomes.category_id', '=', 'income_categories.id')
            ->where(function ($query) {
                $query->whereNull('income_categories.is_archived')
                    ->orWhere('income_categories.is_archived', false);
            })
            ->sum('incomes.amount');

        $expense = (float) Expense::query()
            ->where('expenses.user_id', $userId)
            ->where('expenses.payment_method', $method)
            ->where('expenses.date', '>=', $asOf)
            ->where('expenses.is_voluntary', false)
            ->whereNull('expenses.parent_id')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->leftJoin('friend_splits', 'friend_splits.expense_id', '=', 'expenses.id')
            ->where(function ($query) {
                $query->whereNull('expense_categories.is_archived')
                    ->orWhere('expense_categories.is_archived', false);
            })
            ->sum(DB::raw('CASE WHEN friend_splits.id IS NULL THEN expenses.amount + expenses.gst_amount ELSE friend_splits.paid_by_me_amount END'));

        return round((float) $wallet->opening_balance + $income - $expense, 2);
    }

    public function currentBalanceTotal(int $userId): float
    {
        return round((float) $this->enabledWallets($userId)->sum('current_balance'), 2);
    }

    public function ensureDefaults(int $userId): void
    {
        foreach ($this->options->names('payment_method', $userId) as $method) {
            PaymentWallet::firstOrCreate(
                ['user_id' => $userId, 'payment_method' => $method],
                [
                    'is_enabled' => false,
                    'opening_balance' => 0,
                    'opening_as_of' => now()->toDateString(),
                ]
            );
        }
    }
}
