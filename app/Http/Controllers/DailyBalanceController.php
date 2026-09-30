<?php

namespace App\Http\Controllers;

use App\Models\DailyBalance;
use App\Models\Expense;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $month = $request->get('month', Carbon::today()->format('Y-m'));
        $startOfMonth = Carbon::parse($month.'-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Fetch all incomes and expenses for the month grouped by date
        $incomesByDate = Income::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->selectRaw('date, SUM(amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->all();

        $expensesByDate = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->selectRaw('date, SUM(amount + gst_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->all();

        $savedBalances = DailyBalance::query()
            ->where('user_id', $user->id)
            ->whereBetween('record_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(fn ($b) => $b->record_date->toDateString());

        // Baseline opening balance from prior day
        $lastPriorBalance = DailyBalance::query()
            ->where('user_id', $user->id)
            ->where('record_date', '<', $startOfMonth->toDateString())
            ->orderByDesc('record_date')
            ->first();

        $runningBalance = $lastPriorBalance ? (float) $lastPriorBalance->closing_balance : 0.0;

        $days = [];
        $cursor = $startOfMonth->copy();
        while ($cursor->lte($endOfMonth)) {
            $dStr = $cursor->toDateString();
            $income = (float) ($incomesByDate[$dStr] ?? 0);
            $expense = (float) ($expensesByDate[$dStr] ?? 0);
            $saved = $savedBalances->get($dStr);

            $opening = $saved && $saved->is_opening_manual ? (float) $saved->opening_balance : $runningBalance;
            $adjustment = $saved ? (float) $saved->manual_adjustment : 0.0;
            $closing = $saved && $saved->is_closing_manual
                ? (float) $saved->closing_balance
                : ($opening + $income - $expense + $adjustment);

            $days[$dStr] = [
                'date' => $cursor->copy(),
                'date_str' => $dStr,
                'is_today' => $cursor->isToday(),
                'opening' => round($opening, 2),
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'adjustment' => round($adjustment, 2),
                'closing' => round($closing, 2),
                'is_opening_manual' => $saved?->is_opening_manual ?? false,
                'is_closing_manual' => $saved?->is_closing_manual ?? false,
                'notes' => $saved?->notes,
                'saved_id' => $saved?->id,
            ];

            $runningBalance = $closing;
            $cursor->addDay();
        }

        $todayStr = Carbon::today()->toDateString();
        $todayData = $days[$todayStr] ?? null;

        return view('finance.daily_balances.index', compact('days', 'month', 'todayData', 'todayStr'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'record_date' => ['required', 'date'],
            'opening_balance' => ['nullable', 'numeric'],
            'manual_adjustment' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'is_opening_manual' => ['nullable', 'boolean'],
            'is_closing_manual' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $record = DailyBalance::query()->firstOrNew([
            'user_id' => $user->id,
            'record_date' => $validated['record_date'],
        ]);

        if (array_key_exists('opening_balance', $validated) && $validated['opening_balance'] !== null) {
            $record->opening_balance = (float) $validated['opening_balance'];
            $record->is_opening_manual = true;
        }

        if (array_key_exists('manual_adjustment', $validated) && $validated['manual_adjustment'] !== null) {
            $record->manual_adjustment = (float) $validated['manual_adjustment'];
        }

        if (array_key_exists('closing_balance', $validated) && $validated['closing_balance'] !== null) {
            $record->closing_balance = (float) $validated['closing_balance'];
            $record->is_closing_manual = true;
        }

        if (array_key_exists('notes', $validated)) {
            $record->notes = $validated['notes'];
        }

        $record->save();

        return back()->with('success', 'Daily balance updated for '.Carbon::parse($validated['record_date'])->format('d M Y').'.');
    }
}
