<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\TripDistanceService;
use Carbon\Carbon;

class ScooterTrip extends Model
{
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'daily_record_id',
        'title',
        'date',
        'start_time',
        'start_latitude',
        'start_longitude',
        'start_address',
        'from_label',
        'end_time',
        'end_latitude',
        'end_longitude',
        'end_address',
        'to_label',
        'to_and_fro',
        'stops',
        'distance_km',
        'one_way_km',
        'distance_source',
        'estimated_litres',
        'estimated_fuel_cost',
        'purpose',
        'duration_minutes',
        'speedometer_image',
        'odometer_reading',
        'notes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'start_latitude' => 'float',
        'start_longitude' => 'float',
        'end_latitude' => 'float',
        'end_longitude' => 'float',
        'distance_km' => 'float',
        'one_way_km' => 'float',
        'estimated_litres' => 'float',
        'estimated_fuel_cost' => 'float',
        'duration_minutes' => 'integer',
        'odometer_reading' => 'integer',
        'to_and_fro' => 'boolean',
        'stops' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }

    public function calculateDistance(?TripDistanceService $service = null): ?float
    {
        $service = $service ?? app(TripDistanceService::class);
        $vehicle = $this->vehicle ?? ($this->vehicle_id ? Vehicle::find($this->vehicle_id) : Vehicle::defaultFor($this->user_id));

        $start = ($this->start_latitude && $this->start_longitude)
            ? ['lat' => $this->start_latitude, 'lng' => $this->start_longitude]
            : null;
        $end = ($this->end_latitude && $this->end_longitude)
            ? ['lat' => $this->end_latitude, 'lng' => $this->end_longitude]
            : null;

        $result = $service->calculate(
            $start,
            $end,
            is_array($this->stops) ? $this->stops : [],
            (bool) $this->to_and_fro,
            $vehicle,
            $this->user_id
        );

        $this->one_way_km = $result['one_way_km'];
        $this->distance_km = $result['distance_km'];
        $this->distance_source = $result['distance_source'];
        $this->estimated_litres = $result['estimated_litres'];
        $this->estimated_fuel_cost = $result['estimated_fuel_cost'];

        return $this->distance_km;
    }

    public function calculateDuration(): ?int
    {
        if (! $this->start_time || ! $this->end_time) {
            return null;
        }

        $start = Carbon::parse($this->date->toDateString().' '.$this->start_time);
        $end = Carbon::parse($this->date->toDateString().' '.$this->end_time);

        if ($end->lt($start)) {
            $end->addDay();
        }

        $duration = (int) $start->diffInMinutes($end);
        $this->duration_minutes = $duration;

        return $duration;
    }
}
