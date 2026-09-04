<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\PaymentWallet;
use Illuminate\Support\Collection;

class WalletService
{
    public function enabledWallets(int $userId): Collection
    {
        return PaymentWallet::where('user_id', $userId)
            ->where('is_enabled', true)
            ->orderBy('payment_method')
            ->get()
            ->map(function (PaymentWallet $wallet) use ($userId) {
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
            ->where(function ($q) {
                $q->whereNull('income_categories.is_archived')
                    ->orWhere('income_categories.is_archived', false);
            })
            ->sum('incomes.amount');

        $expense = (float) Expense::query()
            ->where('expenses.user_id', $userId)
            ->where('expenses.payment_method', $method)
            ->where('expenses.date', '>=', $asOf)
            ->where(function ($q) {
                $q->whereNull('expenses.paid_by_type')->orWhere('expenses.paid_by_type', 'me');
            })
            ->where('expenses.is_voluntary', false)
            ->whereNull('expenses.parent_id')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->where(function ($q) {
                $q->whereNull('expense_categories.is_archived')
                    ->orWhere('expense_categories.is_archived', false);
            })
            ->sum('expenses.amount');

        return round((float) $wallet->opening_balance + $income - $expense, 2);
    }

    public function ensureDefaults(int $userId): void
    {
        foreach (['UPI', 'Cash'] as $method) {
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
