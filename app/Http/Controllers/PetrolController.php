<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FuelEntry;
use App\Services\AuditService;
use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PetrolController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $entries = FuelEntry::where('user_id', $user->id)
            ->with('expense')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(15);

        $totalSpent = (float) FuelEntry::where('user_id', $user->id)->sum('amount');
        $totalLitres = (float) FuelEntry::where('user_id', $user->id)->sum('litres');
        $avgPricePerLitre = $totalLitres > 0 ? round($totalSpent / $totalLitres, 2) : 0;
        $latestOdometer = FuelEntry::where('user_id', $user->id)->max('odometer') ?? 0;

        return view('scooter.petrol.index', compact(
            'entries',
            'totalSpent',
            'totalLitres',
            'avgPricePerLitre',
            'latestOdometer'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'nullable',
            'amount' => 'required|numeric|min:1',
            'litres' => 'required|numeric|min:0.1',
            'odometer' => 'nullable|integer',
            'petrol_station' => 'nullable|string|max:150',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'add_as_expense' => 'nullable|boolean',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('receipt_image')) {
            $imagePath = $request->file('receipt_image')->store('petrol_receipts', 'public');
        }

        $pricePerLitre = round($validated['amount'] / $validated['litres'], 2);

        $fuel = DB::transaction(function () use ($request, $validated, $imagePath, $pricePerLitre): FuelEntry {
            $dailyRecord = DailyRecord::firstOrCreate([
                'user_id' => $request->user()->id,
                'record_date' => $validated['date'],
            ]);

            $fuel = FuelEntry::create([
                'user_id' => $request->user()->id,
                'daily_record_id' => $dailyRecord->id,
                'date' => $validated['date'],
                'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
                'amount' => $validated['amount'],
                'litres' => $validated['litres'],
                'price_per_litre' => $pricePerLitre,
                'odometer' => $validated['odometer'] ?? null,
                'petrol_station' => $validated['petrol_station'] ?? null,
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'receipt_image' => $imagePath,
            ]);

            if ($request->boolean('add_as_expense')) {
                $fuel->update(['expense_id' => $this->createExpenseForFuel($request, $fuel)->id]);
            }

            return $fuel;
        });

        AuditService::log('fuel', $fuel->id, 'created', null, $fuel->toArray(), 'Petrol record created');

        return redirect()->route('petrol.index')->with('success', '⛽ Petrol entry recorded successfully!');
    }

    public function edit(FuelEntry $petrol, FinanceService $financeService)
    {
        if ($petrol->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('petrol', $petrol)) {
            return redirect()->route('petrol.index')->with('error', '🔒 This petrol record is locked.');
        }

        return view('scooter.petrol.edit', compact('petrol'));
    }

    public function update(Request $request, FuelEntry $petrol, FinanceService $financeService)
    {
        if ($petrol->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('petrol', $petrol)) {
            return redirect()->route('petrol.index')->with('error', '🔒 This petrol record is locked.');
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'nullable',
            'amount' => 'required|numeric|min:1',
            'litres' => 'required|numeric|min:0.1',
            'odometer' => 'nullable|integer',
            'petrol_station' => 'nullable|string|max:150',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'add_as_expense' => 'nullable|boolean',
            'reason' => 'nullable|string|max:255',
        ]);

        $validated['price_per_litre'] = round($validated['amount'] / $validated['litres'], 2);

        $old = $petrol->only(['amount', 'litres', 'price_per_litre', 'odometer']);
        DB::transaction(function () use ($request, $validated, $petrol): void {
            $petrol->update($validated);

            if ($petrol->expense_id) {
                $this->syncExpenseForFuel($petrol);
            } elseif ($request->boolean('add_as_expense')) {
                $petrol->update(['expense_id' => $this->createExpenseForFuel($request, $petrol)->id]);
            }
        });

        AuditService::log(
            module: 'fuel',
            recordId: $petrol->id,
            action: 'updated',
            oldValues: $old,
            newValues: $petrol->only(['amount', 'litres', 'price_per_litre', 'odometer']),
            reason: $request->input('reason', 'Updated by user')
        );

        return redirect()->route('petrol.index')->with('success', 'Petrol record updated!');
    }

    public function destroy(Request $request, FuelEntry $petrol)
    {
        if ($petrol->user_id !== auth()->id()) {
            abort(403);
        }
        $deleteLinkedExpense = $request->boolean('delete_linked_expense');

        DB::transaction(function () use ($petrol, $deleteLinkedExpense): void {
            $expense = $petrol->expense;
            if ($expense) {
                if ($deleteLinkedExpense) {
                    $expense->delete();
                } else {
                    $petrol->update(['expense_id' => null]);
                }
            }

            $petrol->delete();
        });

        return redirect()->route('petrol.index')->with('success', $deleteLinkedExpense
            ? 'Petrol record and linked expense deleted.'
            : 'Petrol record deleted; linked expense preserved.');
    }

    private function createExpenseForFuel(Request $request, FuelEntry $fuel): Expense
    {
        $category = ExpenseCategory::query()
            ->where('name', 'Petrol')
            ->where(function ($query): void {
                $query->whereNull('user_id')->orWhere('user_id', auth()->id());
            })
            ->orderByDesc('user_id')
            ->first();

        return Expense::create([
            'user_id' => $fuel->user_id,
            'daily_record_id' => $fuel->daily_record_id,
            'category_id' => $category?->id,
            'amount' => $fuel->amount,
            'gst_amount' => 0,
            'date' => $fuel->date,
            'time' => $fuel->time,
            'description' => $fuel->petrol_station ? 'Petrol - '.$fuel->petrol_station : 'Petrol',
            'payment_method' => $fuel->payment_method,
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
            'notes' => $fuel->notes,
        ]);
    }

    private function syncExpenseForFuel(FuelEntry $fuel): void
    {
        $expense = $fuel->expense;
        if (! $expense || $expense->user_id !== $fuel->user_id) {
            return;
        }

        $expense->update([
            'amount' => $fuel->amount,
            'date' => $fuel->date,
            'time' => $fuel->time,
            'description' => $fuel->petrol_station ? 'Petrol - '.$fuel->petrol_station : 'Petrol',
            'payment_method' => $fuel->payment_method,
            'notes' => $fuel->notes,
        ]);
    }
}
