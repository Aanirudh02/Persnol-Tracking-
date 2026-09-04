<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'make',
        'model',
        'year',
        'registration_number',
        'fuel_type',
        'default_mileage_kmpl',
        'actual_mileage_kmpl',
        'tank_capacity_litres',
        'purchase_date',
        'purchase_price',
        'odometer_start',
        'is_default',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'default_mileage_kmpl' => 'float',
        'actual_mileage_kmpl' => 'float',
        'tank_capacity_litres' => 'float',
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'odometer_start' => 'integer',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(ScooterTrip::class);
    }

    public function fuelEntries(): HasMany
    {
        return $this->hasMany(FuelEntry::class);
    }

    public function effectiveMileage(): float
    {
        return (float) ($this->actual_mileage_kmpl ?: $this->default_mileage_kmpl ?: 40);
    }

    public static function defaultFor(int $userId): ?self
    {
        return static::where('user_id', $userId)->where('is_default', true)->first()
            ?? static::where('user_id', $userId)->oldest()->first();
    }
}
