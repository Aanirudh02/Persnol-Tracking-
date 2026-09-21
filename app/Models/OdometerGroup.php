<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OdometerGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'title',
        'status',
        'start_fuel_entry_id',
        'end_fuel_entry_id',
        'start_odometer',
        'end_odometer',
        'total_km',
        'total_litres',
        'total_fuel_cost',
        'calculated_mileage',
        'cost_per_km',
        'notes',
    ];

    protected $casts = [
        'start_odometer' => 'decimal:2',
        'end_odometer' => 'decimal:2',
        'total_km' => 'decimal:2',
        'total_litres' => 'decimal:2',
        'total_fuel_cost' => 'decimal:2',
        'calculated_mileage' => 'decimal:2',
        'cost_per_km' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function startFuelEntry(): BelongsTo
    {
        return $this->belongsTo(FuelEntry::class, 'start_fuel_entry_id')->withTrashed();
    }

    public function endFuelEntry(): BelongsTo
    {
        return $this->belongsTo(FuelEntry::class, 'end_fuel_entry_id')->withTrashed();
    }

    public function readings(): HasMany
    {
        return $this->hasMany(OdometerReading::class)->orderBy('id', 'asc');
    }

    /**
     * Recalculate and update the summary metrics and leg distances for this cycle.
     */
    public function recalculateSummary(): void
    {
        $readings = $this->readings()->get();
        if ($readings->isEmpty()) {
            return;
        }

        $prevKm = null;
        foreach ($readings as $r) {
            if ($r->reading_type === 'source' || $prevKm === null) {
                $r->distance_km = 0;
            } else {
                $r->distance_km = max(0, round((float) $r->odometer_km - $prevKm, 2));
                if ($r->duration_minutes && $r->duration_minutes > 0 && $r->distance_km > 0) {
                    $r->avg_speed_kmh = round($r->distance_km / ($r->duration_minutes / 60), 2);
                }
            }
            $r->saveQuietly();
            $prevKm = (float) $r->odometer_km;
        }

        $first = $readings->first();
        $last = $readings->last();

        $this->start_odometer = (float) $first->odometer_km;

        if ($readings->count() > 1 && ($this->status === 'completed' || $last->reading_type === 'ending')) {
            $this->end_odometer = (float) $last->odometer_km;
            $this->total_km = max(0, round($this->end_odometer - $this->start_odometer, 2));

            // If end fuel entry is linked, use its refill litres as fuel consumed to full
            if ($this->end_fuel_entry_id && $this->endFuelEntry) {
                $litres = (float) $this->endFuelEntry->litres;
                $cost = (float) $this->endFuelEntry->amount;

                if ($litres > 0) {
                    $this->total_litres = $litres;
                    $this->total_fuel_cost = $cost;
                    $this->calculated_mileage = $this->total_km > 0 ? round($this->total_km / $litres, 2) : 0;
                    $this->cost_per_km = $this->total_km > 0 ? round($cost / $this->total_km, 2) : 0;
                }
            }
        }

        $this->save();
    }
}
