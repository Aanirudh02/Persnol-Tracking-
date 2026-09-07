<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\FuelEntry;
use App\Services\AuditService;
use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PetrolController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $entries = FuelEntry::where('user_id', $user->id)
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
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('receipt_image')) {
            $imagePath = $request->file('receipt_image')->store('petrol_receipts', 'public');
        }

        $pricePerLitre = round($validated['amount'] / $validated['litres'], 2);

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
            'reason' => 'nullable|string|max:255',
        ]);

        $validated['price_per_litre'] = round($validated['amount'] / $validated['litres'], 2);

        $old = $petrol->only(['amount', 'litres', 'price_per_litre', 'odometer']);
        $petrol->update($validated);

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
        $petrol->delete();

        return redirect()->route('petrol.index')->with('success', 'Petrol record deleted.');
    }
}
