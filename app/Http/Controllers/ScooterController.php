<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\FuelEntry;
use App\Models\ScooterTrip;
use App\Models\Vehicle;
use App\Services\Maps\MapProviderInterface;
use App\Services\TripDistanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScooterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $activeTrip = ScooterTrip::where('user_id', $user->id)
            ->where('status', 'ongoing')
            ->latest()
            ->first();

        $trips = ScooterTrip::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with('vehicle')
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->paginate(15);

        $totalDistance = (float) ScooterTrip::where('user_id', $user->id)->sum('distance_km');
        $totalTrips = ScooterTrip::where('user_id', $user->id)->where('status', 'completed')->count();
        $totalDuration = (int) ScooterTrip::where('user_id', $user->id)->sum('duration_minutes');
        $totalLitres = (float) ScooterTrip::where('user_id', $user->id)->sum('estimated_litres');
        $totalFuelCost = (float) ScooterTrip::where('user_id', $user->id)->sum('estimated_fuel_cost');
        $latestOdometer = FuelEntry::where('user_id', $user->id)->max('odometer')
            ?? ScooterTrip::where('user_id', $user->id)->max('odometer_reading')
            ?? 0;
        $vehicles = Vehicle::where('user_id', $user->id)->orderByDesc('is_default')->get();
        $defaultVehicle = Vehicle::defaultFor($user->id);

        return view('scooter.trips.index', compact(
            'activeTrip',
            'trips',
            'totalDistance',
            'totalTrips',
            'totalDuration',
            'totalLitres',
            'totalFuelCost',
            'latestOdometer',
            'vehicles',
            'defaultVehicle'
        ));
    }

    public function plan(Request $request)
    {
        $vehicles = Vehicle::where('user_id', $request->user()->id)->orderByDesc('is_default')->get();
        $defaultVehicle = Vehicle::defaultFor($request->user()->id);

        return view('scooter.trips.plan', compact('vehicles', 'defaultVehicle'));
    }

    public function storePlanned(Request $request, TripDistanceService $distanceService, MapProviderInterface $maps)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:150',
            'from_label' => 'required|string|max:255',
            'to_label' => 'required|string|max:255',
            'start_latitude' => 'nullable|numeric',
            'start_longitude' => 'nullable|numeric',
            'end_latitude' => 'nullable|numeric',
            'end_longitude' => 'nullable|numeric',
            'start_address' => 'nullable|string|max:255',
            'end_address' => 'nullable|string|max:255',
            'to_and_fro' => 'nullable|boolean',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'purpose' => 'nullable|string|max:100',
            'date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string',
            'stops' => 'nullable|json',
        ]);

        $date = $validated['date'] ?? Carbon::today()->toDateString();
        $dailyRecord = DailyRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $date,
        ]);

        $vehicleId = $validated['vehicle_id'] ?? Vehicle::defaultFor($request->user()->id)?->id;
        $vehicle = $vehicleId ? Vehicle::where('user_id', $request->user()->id)->find($vehicleId) : null;
        $stops = ! empty($validated['stops']) ? json_decode($validated['stops'], true) : [];

        $startLat = filled($validated['start_latitude'] ?? null) ? $validated['start_latitude'] : null;
        $startLng = filled($validated['start_longitude'] ?? null) ? $validated['start_longitude'] : null;
        $endLat = filled($validated['end_latitude'] ?? null) ? $validated['end_latitude'] : null;
        $endLng = filled($validated['end_longitude'] ?? null) ? $validated['end_longitude'] : null;
        $fromLabel = $validated['from_label'];
        $toLabel = $validated['to_label'];

        [$startLat, $startLng, $startAddress] = $this->resolvePlace(
            $maps,
            $fromLabel,
            $startLat,
            $startLng,
            $validated['start_address'] ?? null,
            'start'
        );
        if ($startLat === null) {
            return back()->withInput()->with('error', 'Could not find start location. Type Goldwins Chinniyampalayam 641062 and pick a suggestion.');
        }

        [$endLat, $endLng, $endAddress] = $this->resolvePlace(
            $maps,
            $toLabel,
            $endLat,
            $endLng,
            $validated['end_address'] ?? null,
            'end'
        );
        if ($endLat === null) {
            return back()->withInput()->with('error', 'Could not find end location. Type Tex Park Road Nehru Nagar West 641014 and pick a suggestion.');
        }

        $start = ['lat' => (float) $startLat, 'lng' => (float) $startLng];
        $end = ['lat' => (float) $endLat, 'lng' => (float) $endLng];

        $calc = $distanceService->calculate(
            $start,
            $end,
            is_array($stops) ? $stops : [],
            $request->boolean('to_and_fro'),
            $vehicle,
            $request->user()->id
        );

        if ($calc['distance_km'] === null) {
            return back()->withInput()->with('error', 'Could not calculate distance between these places. Try different locations.');
        }

        ScooterTrip::create([
            'user_id' => $request->user()->id,
            'vehicle_id' => $vehicle?->id,
            'daily_record_id' => $dailyRecord->id,
            'title' => $validated['title'] ?? 'Scooter Ride',
            'from_label' => $fromLabel,
            'to_label' => $toLabel,
            'date' => $date,
            'start_time' => Carbon::now()->format('H:i:s'),
            'end_time' => Carbon::now()->format('H:i:s'),
            'start_latitude' => $startLat,
            'start_longitude' => $startLng,
            'end_latitude' => $endLat,
            'end_longitude' => $endLng,
            'start_address' => $startAddress,
            'end_address' => $endAddress,
            'to_and_fro' => $request->boolean('to_and_fro'),
            'stops' => $stops,
            'purpose' => $validated['purpose'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'completed',
            'one_way_km' => $calc['one_way_km'],
            'distance_km' => $calc['distance_km'],
            'distance_source' => $calc['distance_source'],
            'estimated_litres' => $calc['estimated_litres'],
            'estimated_fuel_cost' => $calc['estimated_fuel_cost'],
            'duration_minutes' => 0,
        ]);

        $msg = 'Trip logged: '.$calc['one_way_km'].' km one way';
        if ($request->boolean('to_and_fro')) {
            $msg .= ' · '.$calc['distance_km'].' km round trip';
        }
        $msg .= ' · Mileage '.($vehicle?->effectiveMileage() ?? 40).' km/L · ~'.($calc['estimated_litres'] ?? 0).' L.';

        return redirect()->route('scooter.index')->with('success', $msg);
    }

    public function startTrip(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:150',
            'from_label' => 'nullable|string|max:255',
            'to_label' => 'nullable|string|max:255',
            'start_latitude' => 'nullable|numeric',
            'start_longitude' => 'nullable|numeric',
            'start_address' => 'nullable|string|max:255',
            'to_and_fro' => 'nullable|boolean',
            'stops' => 'nullable|json',
            'notes' => 'nullable|string',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'purpose' => 'nullable|string|max:100',
        ]);

        $today = Carbon::today()->toDateString();
        $dailyRecord = DailyRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $today,
        ]);

        $vehicleId = $validated['vehicle_id'] ?? Vehicle::defaultFor($request->user()->id)?->id;

        $trip = ScooterTrip::create([
            'user_id' => $request->user()->id,
            'vehicle_id' => $vehicleId,
            'daily_record_id' => $dailyRecord->id,
            'title' => $validated['title'] ?? 'Scooter Ride',
            'from_label' => $validated['from_label'] ?? null,
            'to_label' => $validated['to_label'] ?? null,
            'date' => $today,
            'start_time' => Carbon::now()->format('H:i:s'),
            'start_latitude' => $validated['start_latitude'] ?? null,
            'start_longitude' => $validated['start_longitude'] ?? null,
            'start_address' => $validated['start_address'] ?? ($validated['from_label'] ?? 'Current Location'),
            'to_and_fro' => $request->boolean('to_and_fro'),
            'stops' => ! empty($validated['stops']) ? json_decode($validated['stops'], true) : null,
            'notes' => $validated['notes'] ?? null,
            'purpose' => $validated['purpose'] ?? null,
            'status' => 'ongoing',
        ]);

        return redirect()->route('scooter.index')->with('success', 'Trip started! Safe riding!');
    }

    public function endTrip(Request $request, ScooterTrip $trip, TripDistanceService $distanceService)
    {
        if ($trip->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'end_latitude' => 'nullable|numeric',
            'end_longitude' => 'nullable|numeric',
            'end_address' => 'nullable|string|max:255',
            'to_label' => 'nullable|string|max:255',
            'odometer_reading' => 'nullable|integer',
            'speedometer_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'notes' => 'nullable|string',
        ]);

        $imagePath = null;
        if ($request->hasFile('speedometer_image')) {
            $imagePath = $request->file('speedometer_image')->store('speedometers', 'public');
        }

        $trip->end_time = Carbon::now()->format('H:i:s');
        $trip->end_latitude = $validated['end_latitude'] ?? null;
        $trip->end_longitude = $validated['end_longitude'] ?? null;
        $trip->end_address = $validated['end_address'] ?? ($validated['to_label'] ?? 'Destination');
        if (! empty($validated['to_label'])) {
            $trip->to_label = $validated['to_label'];
        }
        $trip->odometer_reading = $validated['odometer_reading'] ?? null;
        if ($imagePath) {
            $trip->speedometer_image = $imagePath;
        }
        if (! empty($validated['notes'])) {
            $trip->notes = ($trip->notes ? $trip->notes."\n" : '').$validated['notes'];
        }

        $trip->status = 'completed';
        $trip->calculateDistance($distanceService);
        $trip->calculateDuration();
        $trip->save();

        return redirect()->route('scooter.index')->with(
            'success',
            "Trip ended! {$trip->distance_km} km (~{$trip->estimated_litres} L)."
        );
    }

    public function edit(Request $request, ScooterTrip $trip)
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403);
        }

        $vehicles = Vehicle::where('user_id', $request->user()->id)->orderByDesc('is_default')->get();
        $defaultVehicle = $trip->vehicle ?? Vehicle::defaultFor($request->user()->id);

        return view('scooter.trips.edit', compact('trip', 'vehicles', 'defaultVehicle'));
    }

    public function update(Request $request, ScooterTrip $trip, TripDistanceService $distanceService, MapProviderInterface $maps)
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:150',
            'from_label' => 'required|string|max:255',
            'to_label' => 'required|string|max:255',
            'start_latitude' => 'nullable|numeric',
            'start_longitude' => 'nullable|numeric',
            'end_latitude' => 'nullable|numeric',
            'end_longitude' => 'nullable|numeric',
            'start_address' => 'nullable|string|max:255',
            'end_address' => 'nullable|string|max:255',
            'to_and_fro' => 'nullable|boolean',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        $vehicleId = $validated['vehicle_id'] ?? $trip->vehicle_id;
        $vehicle = $vehicleId ? Vehicle::where('user_id', $request->user()->id)->find($vehicleId) : null;

        [$startLat, $startLng, $startAddress] = $this->resolvePlace(
            $maps,
            $validated['from_label'],
            $validated['start_latitude'] ?? null,
            $validated['start_longitude'] ?? null,
            $validated['start_address'] ?? null,
            'start'
        );
        if ($startLat === null) {
            return back()->withInput()->with('error', 'Could not find start location. Pick a suggestion or use a clearer address.');
        }

        [$endLat, $endLng, $endAddress] = $this->resolvePlace(
            $maps,
            $validated['to_label'],
            $validated['end_latitude'] ?? null,
            $validated['end_longitude'] ?? null,
            $validated['end_address'] ?? null,
            'end'
        );
        if ($endLat === null) {
            return back()->withInput()->with('error', 'Could not find end location. Pick a suggestion or use a clearer address.');
        }

        $calc = $distanceService->calculate(
            ['lat' => (float) $startLat, 'lng' => (float) $startLng],
            ['lat' => (float) $endLat, 'lng' => (float) $endLng],
            [],
            $request->boolean('to_and_fro'),
            $vehicle,
            $request->user()->id
        );

        if ($calc['distance_km'] === null) {
            return back()->withInput()->with('error', 'Could not calculate distance between these places.');
        }

        $trip->update([
            'vehicle_id' => $vehicle?->id,
            'title' => $validated['title'] ?? $trip->title,
            'from_label' => $validated['from_label'],
            'to_label' => $validated['to_label'],
            'date' => $validated['date'] ?? $trip->date,
            'start_latitude' => $startLat,
            'start_longitude' => $startLng,
            'end_latitude' => $endLat,
            'end_longitude' => $endLng,
            'start_address' => $startAddress,
            'end_address' => $endAddress,
            'to_and_fro' => $request->boolean('to_and_fro'),
            'notes' => $validated['notes'] ?? null,
            'status' => 'completed',
            'one_way_km' => $calc['one_way_km'],
            'distance_km' => $calc['distance_km'],
            'distance_source' => $calc['distance_source'],
            'estimated_litres' => $calc['estimated_litres'],
            'estimated_fuel_cost' => $calc['estimated_fuel_cost'],
        ]);

        return redirect()->route('scooter.index')->with(
            'success',
            'Trip updated: '.$calc['distance_km'].' km · ~'.($calc['estimated_litres'] ?? 0).' L.'
        );
    }

    public function destroy(Request $request, ScooterTrip $trip)
    {
        if ($trip->user_id !== $request->user()->id) {
            abort(403);
        }

        $trip->delete();

        return redirect()->route('scooter.index')->with('success', 'Trip deleted.');
    }

    /**
     * @return array{0: float|null, 1: float|null, 2: string|null}
     */
    protected function resolvePlace(
        MapProviderInterface $maps,
        string $label,
        mixed $lat,
        mixed $lng,
        ?string $address,
        string $kind
    ): array {
        if (filled($lat) && filled($lng)) {
            return [(float) $lat, (float) $lng, $address ?: $label];
        }

        $hits = $maps->searchPlaces($label);
        if ($hits === []) {
            return [null, null, null];
        }

        return [(float) $hits[0]['lat'], (float) $hits[0]['lng'], $hits[0]['label']];
    }
}
