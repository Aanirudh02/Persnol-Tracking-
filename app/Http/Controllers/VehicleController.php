<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\MileageService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::where('user_id', $request->user()->id)->orderByDesc('is_default')->orderBy('name')->get();

        return view('vehicles.index', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1980|max:2100',
            'registration_number' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'default_mileage_kmpl' => 'required|numeric|min:1|max:200',
            'tank_capacity_litres' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'odometer_start' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($request->boolean('is_default')) {
            Vehicle::where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        Vehicle::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'is_default' => $request->boolean('is_default'),
            'fuel_type' => $validated['fuel_type'] ?? 'Petrol',
        ]);

        return back()->with('success', 'Vehicle saved.');
    }

    public function update(Request $request, Vehicle $vehicle, MileageService $mileageService)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'year' => 'nullable|integer|min:1980|max:2100',
            'registration_number' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'default_mileage_kmpl' => 'required|numeric|min:1|max:200',
            'tank_capacity_litres' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'odometer_start' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($request->boolean('is_default')) {
            Vehicle::where('user_id', $request->user()->id)->where('id', '!=', $vehicle->id)->update(['is_default' => false]);
        }

        $vehicle->update([
            ...$validated,
            'is_default' => $request->boolean('is_default'),
        ]);

        $mileageService->recompute($vehicle);

        return back()->with('success', 'Vehicle updated.');
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->user_id !== $request->user()->id) {
            abort(403);
        }

        $vehicle->delete();

        return back()->with('success', 'Vehicle removed.');
    }
}
