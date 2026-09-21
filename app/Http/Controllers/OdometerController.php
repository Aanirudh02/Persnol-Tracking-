<?php

namespace App\Http\Controllers;

use App\Models\FuelEntry;
use App\Models\OdometerGroup;
use App\Models\OdometerReading;
use App\Models\Vehicle;
use App\Services\CloudinaryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OdometerController extends Controller
{
    /**
     * Display the main Odometer & Mileage dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Currently active cycle group
        $activeGroup = OdometerGroup::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['readings', 'startFuelEntry', 'vehicle'])
            ->latest()
            ->first();

        $activeReadings = $activeGroup ? $activeGroup->readings : collect();
        $lastReading = $activeReadings->last();

        // Past completed cycles
        $completedGroups = OdometerGroup::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['startFuelEntry', 'endFuelEntry', 'vehicle'])
            ->orderByDesc('id')
            ->paginate(10);

        // Recent fuel entries to link as start or end refuel
        $recentFuelEntries = FuelEntry::where('user_id', $user->id)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->limit(20)
            ->get();

        // User vehicles
        $vehicles = Vehicle::where('user_id', $user->id)->orderByDesc('is_default')->get();
        $defaultVehicle = Vehicle::defaultFor($user->id);

        // Overall statistics
        $totalKmLogged = (float) OdometerGroup::where('user_id', $user->id)->sum('total_km');
        $averageMileage = (float) OdometerGroup::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('calculated_mileage', '>', 0)
            ->avg('calculated_mileage');
        $bestMileage = (float) OdometerGroup::where('user_id', $user->id)
            ->where('status', 'completed')
            ->max('calculated_mileage');
        $latestOdometer = (float) (
            $lastReading?->odometer_km
            ?? OdometerReading::where('user_id', $user->id)->max('odometer_km')
            ?? FuelEntry::where('user_id', $user->id)->max('odometer')
            ?? 0
        );

        // All readings for the CRUD table list with search and filter
        $readingsQuery = OdometerReading::where('user_id', $user->id)
            ->with(['group', 'vehicle']);

        if ($request->filled('type')) {
            $readingsQuery->where('reading_type', $request->type);
        }

        if ($request->filled('cycle_id')) {
            $readingsQuery->where('odometer_group_id', $request->cycle_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $readingsQuery->where(function ($q) use ($search) {
                $q->where('trip_name', 'like', "%{$search}%")
                    ->orWhere('source_location', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $allReadings = $readingsQuery->orderByDesc('reading_date')
            ->orderByDesc('reading_time')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('odometer.index', compact(
            'activeGroup',
            'activeReadings',
            'lastReading',
            'completedGroups',
            'recentFuelEntries',
            'vehicles',
            'defaultVehicle',
            'totalKmLogged',
            'averageMileage',
            'bestMileage',
            'latestOdometer',
            'allReadings'
        ));
    }

    /**
     * Store a new reading (Source, Intermediate, or Ending).
     */
    public function store(Request $request, CloudinaryService $cloudinary): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'reading_type' => 'required|in:source,intermediate,ending',
            'odometer_km' => 'required|numeric|min:0',
            'reading_date' => 'required|date',
            'reading_time' => 'nullable|string|max:10',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'trip_name' => 'nullable|string|max:150',
            'source_location' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'start_fuel_entry_id' => 'nullable|exists:fuel_entries,id',
            'end_fuel_entry_id' => 'nullable|exists:fuel_entries,id',
            'manual_litres' => 'nullable|numeric|min:0',
            'manual_fuel_cost' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'odometer_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'group_title' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Process optional image upload (to Cloudinary or fallback to local public disk)
        $imagePath = null;
        if ($request->hasFile('odometer_image')) {
            $imagePath = $cloudinary->upload($request->file('odometer_image'), 'odometer');
        }

        $vehicleId = $validated['vehicle_id'] ?? Vehicle::defaultFor($user->id)?->id;
        $date = $validated['reading_date'];
        $time = $validated['reading_time'] ? Carbon::parse($validated['reading_time'])->format('H:i:s') : Carbon::now()->format('H:i:s');
        $odometerKm = round((float) $validated['odometer_km'], 2);

        // -------------------------------------------------------------
        // 1. SOURCE READING (Starts a new cycle group)
        // -------------------------------------------------------------
        if ($validated['reading_type'] === 'source') {
            // Close any existing active group so the new cycle takes over
            $existingActive = OdometerGroup::where('user_id', $user->id)
                ->where('status', 'active')
                ->get();

            foreach ($existingActive as $active) {
                $active->status = 'completed';
                $active->recalculateSummary();
            }

            // Auto-generate title e.g. "Week of 21 Sep – 27 Sep 2026"
            $cDate = Carbon::parse($date);
            $defaultTitle = 'Week of '.$cDate->copy()->startOfWeek()->format('d M').' – '.$cDate->copy()->endOfWeek()->format('d M Y');
            $title = ! empty($validated['group_title']) ? $validated['group_title'] : $defaultTitle;

            $group = OdometerGroup::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicleId,
                'title' => $title,
                'status' => 'active',
                'start_fuel_entry_id' => $validated['start_fuel_entry_id'] ?? null,
                'start_odometer' => $odometerKm,
                'notes' => $validated['notes'] ?? null,
            ]);

            OdometerReading::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicleId,
                'odometer_group_id' => $group->id,
                'reading_type' => 'source',
                'odometer_km' => $odometerKm,
                'reading_date' => $date,
                'reading_time' => $time,
                'trip_name' => $validated['trip_name'] ?: 'Starting Refuel / Cycle Start',
                'source_location' => null, // Requirement: no source destination for first entry
                'destination' => null,
                'distance_km' => 0,
                'odometer_image' => $imagePath,
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()->route('odometer.index')->with(
                'success',
                "New cycle \"{$title}\" started with odometer at {$odometerKm} km!"
            );
        }

        // -------------------------------------------------------------
        // Find current active cycle for Intermediate or Ending readings
        // -------------------------------------------------------------
        $group = OdometerGroup::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (! $group) {
            return back()->withInput()->with(
                'error',
                'No active cycle found. Please record a "Source" reading first to start a cycle.'
            );
        }

        $lastReading = $group->readings()->latest('id')->first();
        $prevKm = $lastReading ? (float) $lastReading->odometer_km : (float) $group->start_odometer;

        // Calculate delta distance for this trip leg
        $distanceKm = max(0, round($odometerKm - $prevKm, 2));

        // Calculate average speed if duration provided
        $avgSpeed = null;
        if (! empty($validated['duration_minutes']) && $validated['duration_minutes'] > 0 && $distanceKm > 0) {
            $hours = $validated['duration_minutes'] / 60;
            $avgSpeed = round($distanceKm / $hours, 2);
        }

        // -------------------------------------------------------------
        // 2. INTERMEDIATE READING (Trip leg inside the active group)
        // -------------------------------------------------------------
        if ($validated['reading_type'] === 'intermediate') {
            OdometerReading::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicleId,
                'odometer_group_id' => $group->id,
                'reading_type' => 'intermediate',
                'odometer_km' => $odometerKm,
                'reading_date' => $date,
                'reading_time' => $time,
                'trip_name' => $validated['trip_name'] ?: 'Intermediate Leg',
                'source_location' => $validated['source_location'] ?? null,
                'destination' => $validated['destination'] ?? null,
                'distance_km' => $distanceKm,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'avg_speed_kmh' => $avgSpeed,
                'odometer_image' => $imagePath,
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()->route('odometer.index')->with(
                'success',
                "Leg recorded! {$distanceKm} km (Odometer: {$odometerKm} km)."
            );
        }

        // -------------------------------------------------------------
        // 3. ENDING READING (Closes cycle & computes mileage)
        // -------------------------------------------------------------
        if ($validated['reading_type'] === 'ending') {
            OdometerReading::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicleId,
                'odometer_group_id' => $group->id,
                'reading_type' => 'ending',
                'odometer_km' => $odometerKm,
                'reading_date' => $date,
                'reading_time' => $time,
                'trip_name' => $validated['trip_name'] ?: 'Final Refuel / Cycle End',
                'source_location' => $validated['source_location'] ?? null,
                'destination' => $validated['destination'] ?? null,
                'distance_km' => $distanceKm,
                'duration_minutes' => $validated['duration_minutes'] ?? null,
                'avg_speed_kmh' => $avgSpeed,
                'odometer_image' => $imagePath,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Mark group completed and calculate totals
            $group->status = 'completed';
            $group->end_fuel_entry_id = $validated['end_fuel_entry_id'] ?? null;
            $group->end_odometer = $odometerKm;
            $totalKm = max(0, round($odometerKm - (float) $group->start_odometer, 2));
            $group->total_km = $totalKm;

            // Resolve fuel consumed
            $litres = 0;
            $cost = 0;

            if ($group->end_fuel_entry_id) {
                $endFuel = FuelEntry::find($group->end_fuel_entry_id);
                if ($endFuel) {
                    $litres = (float) $endFuel->litres;
                    $cost = (float) $endFuel->amount;
                }
            } elseif (! empty($validated['manual_litres']) && $validated['manual_litres'] > 0) {
                $litres = (float) $validated['manual_litres'];
                $cost = (float) ($validated['manual_fuel_cost'] ?? 0);
            }

            if ($litres > 0) {
                $group->total_litres = $litres;
                $group->total_fuel_cost = $cost;
                $group->calculated_mileage = $totalKm > 0 ? round($totalKm / $litres, 2) : 0;
                $group->cost_per_km = $totalKm > 0 ? round($cost / $totalKm, 2) : 0;
            }

            if (! empty($validated['notes'])) {
                $group->notes = ($group->notes ? $group->notes."\n" : '').$validated['notes'];
            }

            $group->save();

            $mileageMsg = $group->calculated_mileage
                ? " Mileage: {$group->calculated_mileage} km/L (₹{$group->cost_per_km}/km)."
                : '';

            return redirect()->route('odometer.index')->with(
                'success',
                "Cycle \"{$group->title}\" completed! Total {$totalKm} km travelled.{$mileageMsg}"
            );
        }

        return redirect()->route('odometer.index');
    }

    /**
     * Show details of a specific cycle with leg-by-leg timeline.
     */
    public function show(Request $request, OdometerGroup $group): View
    {
        if ($group->user_id !== $request->user()->id) {
            abort(403);
        }

        $group->load(['readings', 'startFuelEntry', 'endFuelEntry', 'vehicle']);

        return view('odometer.show', compact('group'));
    }

    /**
     * Update an existing odometer reading.
     */
    public function updateReading(Request $request, OdometerReading $reading, CloudinaryService $cloudinary): RedirectResponse
    {
        if ($reading->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'odometer_km' => 'required|numeric|min:0',
            'reading_date' => 'required|date',
            'reading_time' => 'nullable|string|max:10',
            'trip_name' => 'nullable|string|max:150',
            'source_location' => 'nullable|string|max:255',
            'destination' => 'nullable|string|max:255',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'odometer_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('odometer_image')) {
            $validated['odometer_image'] = $cloudinary->upload($request->file('odometer_image'), 'odometer');
        }

        if (! empty($validated['reading_time'])) {
            $validated['reading_time'] = Carbon::parse($validated['reading_time'])->format('H:i:s');
        }

        $reading->update($validated);

        if ($reading->group) {
            $reading->group->recalculateSummary();
        }

        return back()->with('success', 'Reading updated successfully!');
    }

    /**
     * Update cycle title or notes.
     */
    public function updateGroup(Request $request, OdometerGroup $group): RedirectResponse
    {
        if ($group->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $group->update($validated);

        return back()->with('success', 'Cycle updated successfully!');
    }

    /**
     * Delete an individual reading and recalculate group metrics.
     */
    public function destroyReading(Request $request, OdometerReading $reading): RedirectResponse
    {
        if ($reading->user_id !== $request->user()->id) {
            abort(403);
        }

        $group = $reading->group;
        $reading->delete();

        if ($group) {
            $group->recalculateSummary();
        }

        return back()->with('success', 'Odometer reading deleted.');
    }

    /**
     * Delete an entire cycle group.
     */
    public function destroyGroup(Request $request, OdometerGroup $group): RedirectResponse
    {
        if ($group->user_id !== $request->user()->id) {
            abort(403);
        }

        $group->readings()->delete();
        $group->delete();

        return redirect()->route('odometer.index')->with('success', 'Cycle and associated readings deleted.');
    }
}
