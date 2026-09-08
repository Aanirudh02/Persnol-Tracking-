<?php

namespace App\Http\Controllers;

use App\Models\CreditDebt;
use App\Models\Expense;
use App\Models\Friend;
use App\Models\Income;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\FinanceService;
use App\Services\FriendBalanceService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function index(
        Request $request,
        FinanceService $financeService,
        WalletService $walletService,
        FriendBalanceService $balanceService
    ): View {
        $user = $request->user();
        $stats = $financeService->getMonthlyStats($user->id);
        $weeklyStats = $financeService->getWeeklyStats($user->id);

        $recentExpenses = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->with(['category', 'friendSplit.friend'])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $recentIncomes = Income::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $pendingPayments = Payment::query()
            ->where('user_id', $user->id)
            ->where('status', 'Pending')
            ->orderBy('date')
            ->take(5)
            ->get();

        $friends = Friend::query()
            ->where('user_id', $user->id)
            ->with(['friendSplits', 'creditDebts.payments', 'settlements'])
            ->get();

        $friendBalances = [];
        $totalOwedToMe = 0;
        $totalIOwe = 0;

        foreach ($friends as $friend) {
            $balance = $balanceService->forFriend($friend);
            $friendBalances[] = [
                'friend' => $friend,
                'balance' => $balance,
            ];

            if ($balance['net'] > 0) {
                $totalOwedToMe += $balance['net'];
            } elseif ($balance['net'] < 0) {
                $totalIOwe += abs($balance['net']);
            }
        }

        $categorySpending = $financeService->monthlyExpenseByCategory($user->id);
        $paymentMethodSpending = $financeService->monthlyExpenseByPaymentMethod($user->id);
        $wallets = $walletService->displayWallets($user->id);
        $currentBalance = $walletService->currentBalanceTotal($user->id);
        $openCredits = (float) CreditDebt::query()->where('user_id', $user->id)->where('type', 'credit')->get()->sum(fn ($item) => $item->remaining());
        $openDebts = (float) CreditDebt::query()->where('user_id', $user->id)->where('type', 'debt')->get()->sum(fn ($item) => $item->remaining());

        $sections = array_merge([
            'show_wallet_balances' => true,
            'show_total_expense' => true,
            'show_current_balance' => true,
            'show_expense_by_payment_type' => true,
            'show_expense_by_category' => true,
            'show_friend_overview' => true,
        ], Setting::getVal('finance_dashboard_sections', []));

        return view('finance.index', compact(
            'stats',
            'weeklyStats',
            'recentExpenses',
            'recentIncomes',
            'pendingPayments',
            'friendBalances',
            'totalOwedToMe',
            'totalIOwe',
            'categorySpending',
            'paymentMethodSpending',
            'wallets',
            'currentBalance',
            'openCredits',
            'openDebts',
            'sections'
        ));
    }
}
