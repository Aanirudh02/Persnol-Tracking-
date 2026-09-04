<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Payment;
use App\Models\Friend;
use App\Models\CreditDebt;
use App\Services\FinanceService;
use App\Services\WalletService;
use Carbon\Carbon;

class FinanceDashboardController extends Controller
{
    public function index(Request $request, FinanceService $financeService, WalletService $walletService)
    {
        $user = $request->user();
        $stats = $financeService->getMonthlyStats($user->id);

        $recentExpenses = Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $recentIncomes = Income::where('user_id', $user->id)
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $pendingPayments = Payment::where('user_id', $user->id)
            ->where('status', 'Pending')
            ->orderBy('date')
            ->take(5)
            ->get();

        $friends = Friend::where('user_id', $user->id)->get();
        $friendBalances = [];
        $totalOwedToMe = 0;
        $totalIOwe = 0;

        foreach ($friends as $f) {
            $bal = $f->getBalance();
            $friendBalances[] = [
                'friend' => $f,
                'balance' => $bal,
            ];
            if ($bal['net'] > 0) {
                $totalOwedToMe += $bal['net'];
            } else {
                $totalIOwe += abs($bal['net']);
            }
        }

        $startOfMonth = Carbon::today()->startOfMonth()->toDateString();
        $categorySpending = Expense::where('expenses.user_id', $user->id)
            ->where('expenses.date', '>=', $startOfMonth)
            ->whereNull('expenses.parent_id')
            ->where('expenses.is_voluntary', false)
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->where('expense_categories.is_archived', false)
            ->selectRaw('expense_categories.name, sum(expenses.amount) as total, expense_categories.color')
            ->groupBy('expense_categories.name', 'expense_categories.color')
            ->orderByDesc('total')
            ->get();

        $wallets = $walletService->enabledWallets($user->id);
        $openCredits = (float) CreditDebt::where('user_id', $user->id)->where('type', 'credit')->whereNotIn('status', ['fully_paid'])->get()->sum(fn ($i) => $i->remaining());
        $openDebts = (float) CreditDebt::where('user_id', $user->id)->where('type', 'debt')->whereNotIn('status', ['fully_paid'])->get()->sum(fn ($i) => $i->remaining());

        return view('finance.index', compact(
            'stats',
            'recentExpenses',
            'recentIncomes',
            'pendingPayments',
            'friendBalances',
            'totalOwedToMe',
            'totalIOwe',
            'categorySpending',
            'wallets',
            'openCredits',
            'openDebts'
        ));
    }
}
