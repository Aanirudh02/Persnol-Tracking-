<?php

namespace App\Models;

use App\Services\CloudinaryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OdometerReading extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'odometer_group_id',
        'reading_type',
        'odometer_km',
        'reading_date',
        'reading_time',
        'trip_name',
        'source_location',
        'destination',
        'distance_km',
        'duration_minutes',
        'avg_speed_kmh',
        'odometer_image',
        'notes',
    ];

    protected $casts = [
        'odometer_km' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'avg_speed_kmh' => 'decimal:2',
        'duration_minutes' => 'integer',
        'reading_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(OdometerGroup::class, 'odometer_group_id')->withTrashed();
    }

    /**
     * Get the accessible full image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return CloudinaryService::url($this->odometer_image);
    }
}
