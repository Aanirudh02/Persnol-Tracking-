<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Services\AuditService;
use App\Services\FinanceService;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Income::where('user_id', $user->id)->with('category');

        if ($request->filled('source')) {
            $query->where('source', 'like', "%{$request->source}%");
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        $incomes = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = IncomeCategory::all();
        $totalAmount = (clone $query)->sum('amount');

        return view('finance.income.index', compact('incomes', 'categories', 'totalAmount'));
    }

    public function create(OptionsService $options)
    {
        $categories = IncomeCategory::all();
        $paymentMethods = $options->names('payment_method');
        $incomeSources = $options->names('income_source');

        return view('finance.income.create', compact('categories', 'paymentMethods', 'incomeSources'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|exists:income_categories,id',
            'source' => 'required|string|max:100',
            'date' => 'required|date',
            'time' => 'nullable',
            'payment_method' => 'required|string',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $dailyRecord = DailyRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $validated['date'],
        ]);

        $income = Income::create([
            'user_id' => $request->user()->id,
            'daily_record_id' => $dailyRecord->id,
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'source' => $validated['source'],
            'date' => $validated['date'],
            'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
            'payment_method' => $validated['payment_method'],
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditService::log('income', $income->id, 'created', null, $income->toArray(), 'Income created');

        return redirect()->route('income.index')->with('success', 'Income recorded successfully!');
    }

    public function edit(Income $income, FinanceService $financeService, OptionsService $options)
    {
        if ($income->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('income', $income)) {
            return redirect()->route('income.index')->with('error', '🔒 This income record is locked.');
        }

        $categories = IncomeCategory::all();
        $paymentMethods = $options->names('payment_method');
        $incomeSources = $options->names('income_source');

        return view('finance.income.edit', compact('income', 'categories', 'paymentMethods', 'incomeSources'));
    }

    public function update(Request $request, Income $income, FinanceService $financeService)
    {
        if ($income->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('income', $income)) {
            return redirect()->route('income.index')->with('error', '🔒 This income record is locked.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'nullable|exists:income_categories,id',
            'source' => 'required|string|max:100',
            'date' => 'required|date',
            'time' => 'nullable',
            'payment_method' => 'required|string',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldValues = $income->only(['amount', 'source', 'date', 'payment_method']);
        $income->update($validated);

        AuditService::log(
            module: 'income',
            recordId: $income->id,
            action: 'updated',
            oldValues: $oldValues,
            newValues: $income->only(['amount', 'source', 'date', 'payment_method']),
            reason: $request->input('reason', 'Updated by user')
        );

        return redirect()->route('income.index')->with('success', 'Income record updated!');
    }

    public function destroy(Request $request, Income $income)
    {
        if ($income->user_id !== auth()->id()) {
            abort(403);
        }

        AuditService::log('income', $income->id, 'deleted', $income->toArray(), null, $request->input('reason', 'Deleted by user'));
        $income->delete();

        return redirect()->route('income.index')->with('success', 'Income record deleted.');
    }
}
