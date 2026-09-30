<?php

namespace App\Http\Controllers;

use App\Models\Saving;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavingsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Saving::query()->where('user_id', $user->id);

        if ($request->filled('goal')) {
            $query->where('goal_or_category', $request->string('goal'));
        }
        if ($request->filled('status') && $request->string('status') !== 'all') {
            $query->where('status', $request->string('status'));
        }

        $savings = $query->orderByDesc('saved_date')->orderByDesc('created_at')->paginate(15)->withQueryString();

        $allSavings = Saving::query()->where('user_id', $user->id)->get();
        $totalSaved = (float) $allSavings->sum('amount');
        $withdrawnTotal = (float) $allSavings->sum('withdrawn_amount');
        $netAvailable = max(0, $totalSaved - $withdrawnTotal);

        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthSaved = (float) $allSavings
            ->where('saved_date', '>=', $currentMonth)
            ->sum('amount');

        $goals = Saving::query()
            ->where('user_id', $user->id)
            ->whereNotNull('goal_or_category')
            ->where('goal_or_category', '!=', '')
            ->distinct()
            ->pluck('goal_or_category');

        return view('finance.savings.index', compact(
            'savings',
            'totalSaved',
            'withdrawnTotal',
            'netAvailable',
            'currentMonthSaved',
            'goals'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source' => ['required', 'string', 'max:100'],
            'goal_or_category' => ['nullable', 'string', 'max:100'],
            'saved_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,locked,withdrawn'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'active';

        Saving::create($validated);

        return back()->with('success', 'Savings record of ₹'.number_format((float) $validated['amount'], 2).' added successfully.');
    }

    public function edit(Request $request, Saving $saving): View
    {
        abort_if($saving->user_id !== $request->user()->id, 403);

        $goals = Saving::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('goal_or_category')
            ->where('goal_or_category', '!=', '')
            ->distinct()
            ->pluck('goal_or_category');

        return view('finance.savings.edit', compact('saving', 'goals'));
    }

    public function update(Request $request, Saving $saving): RedirectResponse
    {
        abort_if($saving->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source' => ['required', 'string', 'max:100'],
            'goal_or_category' => ['nullable', 'string', 'max:100'],
            'saved_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:active,locked,withdrawn'],
            'withdrawn_amount' => ['nullable', 'numeric', 'min:0', 'max:'.$request->input('amount')],
        ]);

        $saving->update($validated);

        return redirect()->route('savings.index')->with('success', 'Savings record updated.');
    }

    public function withdraw(Request $request, Saving $saving): RedirectResponse
    {
        abort_if($saving->user_id !== $request->user()->id, 403);

        $net = $saving->netAvailable();
        $validated = $request->validate([
            'withdraw_amount' => ['required', 'numeric', 'min:0.01', 'max:'.$net],
            'notes' => ['nullable', 'string'],
        ]);

        $newWithdrawn = (float) $saving->withdrawn_amount + (float) $validated['withdraw_amount'];
        $saving->withdrawn_amount = $newWithdrawn;
        if ($saving->netAvailable() <= 0.01) {
            $saving->status = 'withdrawn';
        }
        if (! empty($validated['notes'])) {
            $saving->notes = trim(($saving->notes ?? '')."\nWithdrawal: ₹".number_format((float) $validated['withdraw_amount'], 2).' ('.$validated['notes'].')');
        }
        $saving->save();

        return back()->with('success', 'Withdrew ₹'.number_format((float) $validated['withdraw_amount'], 2).' from savings.');
    }

    public function destroy(Request $request, Saving $saving): RedirectResponse
    {
        abort_if($saving->user_id !== $request->user()->id, 403);

        $saving->delete();

        return redirect()->route('savings.index')->with('success', 'Savings record deleted.');
    }
}
