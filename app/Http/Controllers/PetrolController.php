<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FuelEntry;
use App\Models\Vehicle;
use App\Services\AuditService;
use App\Services\CloudinaryService;
use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PetrolController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Ensure default TVS Pep+ vehicle exists
        $defaultVehicle = Vehicle::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'TVS Pep+'],
            [
                'make' => 'TVS',
                'model' => 'Scooty Pep+',
                'default_mileage_kmpl' => 45,
                'fuel_type' => 'Petrol',
                'is_default' => true,
            ]
        );

        if (! Vehicle::where('user_id', $user->id)->where('is_default', true)->exists()) {
            $defaultVehicle->update(['is_default' => true]);
        }

        // Associate any unassigned petrol records to TVS Pep+
        FuelEntry::where('user_id', $user->id)
            ->whereNull('vehicle_id')
            ->update(['vehicle_id' => $defaultVehicle->id]);

        $vehicles = Vehicle::where('user_id', $user->id)->orderByDesc('is_default')->orderBy('name')->get();

        $query = FuelEntry::where('user_id', $user->id)
            ->with(['expense', 'vehicle']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->integer('vehicle_id'));
        }

        $entries = $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $baseStatsQuery = FuelEntry::where('user_id', $user->id);
        if ($request->filled('vehicle_id')) {
            $baseStatsQuery->where('vehicle_id', $request->integer('vehicle_id'));
        }

        $totalSpent = (float) (clone $baseStatsQuery)->sum('amount');
        $totalLitres = (float) (clone $baseStatsQuery)->sum('litres');
        $avgPricePerLitre = $totalLitres > 0 ? round($totalSpent / $totalLitres, 2) : 0;
        $latestOdometer = (clone $baseStatsQuery)->max('odometer') ?? 0;

        $fillLog = $this->buildFillLog($user->id);

        // Last fill with an odometer per vehicle, used by the live preview in the add form
        $vehicleFuelSnapshots = $vehicles->mapWithKeys(function (Vehicle $vehicle) use ($fillLog) {
            $last = collect($fillLog)->where('vehicle_id', $vehicle->id)->whereNotNull('odometer')->last();

            return [$vehicle->id => [
                'name' => $vehicle->name,
                'last_odometer' => $last['odometer'] ?? null,
                'last_date' => isset($last['date']) ? Carbon::parse($last['date'])->format('d M Y') : null,
            ]];
        });

        return view('scooter.petrol.index', compact(
            'entries',
            'vehicles',
            'defaultVehicle',
            'totalSpent',
            'totalLitres',
            'avgPricePerLitre',
            'latestOdometer',
            'fillLog',
            'vehicleFuelSnapshots'
        ));
    }

    /**
     * Chronological fill log per vehicle: previous odometer, distance since the
     * previous fill and full-tank mileage (distance ÷ litres of this fill).
     *
     * @return array<int, array{vehicle_id: int|null, date: string, odometer: int|null, previous_odometer: int|null, previous_date: string|null, distance: int|null, mileage: float|null}>
     */
    private function buildFillLog(int $userId): array
    {
        $fills = FuelEntry::where('user_id', $userId)
            ->orderBy('date')
            ->orderByRaw("COALESCE(time, '00:00:00')")
            ->orderBy('id')
            ->get(['id', 'vehicle_id', 'date', 'time', 'odometer', 'litres']);

        $lastByVehicle = [];
        $log = [];

        foreach ($fills as $fill) {
            $vehicleKey = $fill->vehicle_id ?? 0;
            $previous = $lastByVehicle[$vehicleKey] ?? null;
            $distance = null;
            $mileage = null;

            if ($fill->odometer && $previous && $fill->odometer > $previous['odometer']) {
                $distance = $fill->odometer - $previous['odometer'];
                $mileage = (float) $fill->litres > 0 ? round($distance / (float) $fill->litres, 1) : null;
            }

            $log[$fill->id] = [
                'vehicle_id' => $fill->vehicle_id,
                'date' => $fill->date->toDateString(),
                'odometer' => $fill->odometer,
                'previous_odometer' => $previous['odometer'] ?? null,
                'previous_date' => $previous['date'] ?? null,
                'distance' => $distance,
                'mileage' => $mileage,
            ];

            if ($fill->odometer) {
                $lastByVehicle[$vehicleKey] = ['odometer' => $fill->odometer, 'date' => $fill->date->toDateString()];
            }
        }

        return $log;
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $defaultVehicle = Vehicle::defaultFor($user->id) ?? Vehicle::where('user_id', $user->id)->first();

        $validated = $request->validate([
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
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
            $imagePath = app(CloudinaryService::class)->upload($request->file('receipt_image'), 'petrol_receipts');
        }

        $pricePerLitre = round($validated['amount'] / $validated['litres'], 2);
        $vehicleId = isset($validated['vehicle_id'])
            ? Vehicle::where('user_id', $user->id)->findOrFail($validated['vehicle_id'])->id
            : $defaultVehicle?->id;

        if (! empty($validated['odometer'])) {
            $previousFill = FuelEntry::where('user_id', $user->id)
                ->where('vehicle_id', $vehicleId)
                ->whereNotNull('odometer')
                ->where('date', '<=', $validated['date'])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->first();

            if ($previousFill && (int) $validated['odometer'] < $previousFill->odometer) {
                return back()->withInput()->with(
                    'error',
                    "Odometer {$validated['odometer']} km is lower than the previous fill ({$previousFill->odometer} km on ".$previousFill->date->format('d M Y').') for this vehicle.'
                );
            }
        }

        $fuel = DB::transaction(function () use ($request, $validated, $imagePath, $pricePerLitre, $vehicleId): FuelEntry {
            $dailyRecord = DailyRecord::firstOrCreate([
                'user_id' => $request->user()->id,
                'record_date' => $validated['date'],
            ]);

            $fuel = FuelEntry::create([
                'user_id' => $request->user()->id,
                'vehicle_id' => $vehicleId,
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

        $logEntry = $this->buildFillLog($user->id)[$fuel->id] ?? null;
        $sinceLastFill = $logEntry && $logEntry['distance']
            ? " {$logEntry['distance']} km since previous fill ({$logEntry['previous_odometer']} km)".($logEntry['mileage'] ? " · {$logEntry['mileage']} km/L." : '.')
            : '';

        return redirect()->route('petrol.index')->with('success', '⛽ Petrol entry recorded successfully!'.$sinceLastFill);
    }

    public function edit(FuelEntry $petrol, FinanceService $financeService)
    {
        if ($petrol->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $financeService->canEdit('petrol', $petrol)) {
            return redirect()->route('petrol.index')->with('error', '🔒 This petrol record is locked.');
        }

        $vehicles = Vehicle::where('user_id', auth()->id())->orderByDesc('is_default')->orderBy('name')->get();

        return view('scooter.petrol.edit', compact('petrol', 'vehicles'));
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
            'vehicle_id' => 'nullable|integer|exists:vehicles,id',
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

        $old = $petrol->only(['vehicle_id', 'amount', 'litres', 'price_per_litre', 'odometer']);
        DB::transaction(function () use ($request, $validated, $petrol): void {
            $petrol->update($validated);

            $linkedExpense = $petrol->expense;
            if ($petrol->expense_id && $linkedExpense && ! $linkedExpense->trashed()) {
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
            newValues: $petrol->only(['vehicle_id', 'amount', 'litres', 'price_per_litre', 'odometer']),
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
            : 'Petrol record deleted; linked expense preserved and disassociated.');
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

    public function linkExpense(Request $request, FuelEntry $petrol): RedirectResponse
    {
        if ($petrol->user_id !== auth()->id()) {
            abort(403);
        }

        if ($petrol->expense_id && $petrol->expense && ! $petrol->expense->trashed()) {
            return back()->with('info', 'This petrol entry is already linked to an active expense.');
        }

        DB::transaction(function () use ($request, $petrol): void {
            $expense = $this->createExpenseForFuel($request, $petrol);
            $petrol->update(['expense_id' => $expense->id]);
        });

        return back()->with('success', '⛽ Petrol fill logged as Normal Expense successfully!');
    }

    public function statement(Request $request): View
    {
        $user = $request->user();
        $period = $request->get('period', 'month');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $vehicleId = $request->get('vehicle_id');

        $today = Carbon::today();
        [$startDate, $endDate] = match ($period) {
            'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'year' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
            'custom' => [$fromDate ?: $today->copy()->startOfMonth()->toDateString(), $toDate ?: $today->toDateString()],
            default => [null, null],
        };

        $query = FuelEntry::query()
            ->where('user_id', $user->id)
            ->with(['vehicle', 'expense']);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        if (! empty($vehicleId) && $vehicleId !== 'all') {
            $query->where('vehicle_id', (int) $vehicleId);
        }

        $entries = $query->orderBy('date')->orderBy('odometer')->get();
        $vehicles = Vehicle::where('user_id', $user->id)->orderBy('name')->get();

        $totalSpent = round((float) $entries->sum('amount'), 2);
        $totalLitres = round((float) $entries->sum('litres'), 2);
        $avgPricePerLitre = $totalLitres > 0 ? round($totalSpent / $totalLitres, 2) : 0.0;

        // Mileage between consecutive fill-ups of the same vehicle (not across vehicles,
        // and not reset by the period filter cutting off the previous fill)
        $fillLog = $this->buildFillLog($user->id);
        $enhancedEntries = $entries->map(fn (FuelEntry $entry) => [
            'entry' => $entry,
            'distance' => $fillLog[$entry->id]['distance'] ?? null,
            'mileage' => $fillLog[$entry->id]['mileage'] ?? null,
        ]);

        $entriesDesc = $enhancedEntries->reverse()->values();

        return view('scooter.petrol.statement', compact(
            'entriesDesc',
            'vehicles',
            'totalSpent',
            'totalLitres',
            'avgPricePerLitre',
            'period',
            'fromDate',
            'toDate',
            'startDate',
            'endDate',
            'vehicleId'
        ));
    }
}
