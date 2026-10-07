<?php

namespace App\Models;

use App\Services\CloudinaryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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

    /**
     * Add the previous reading (km + date) of the same vehicle to each row,
     * so the log can show "previous → this entry (+distance)".
     */
    public function scopeWithPreviousReading(Builder $query): Builder
    {
        $previous = fn (string $column) => DB::table('odometer_readings as prev')
            ->select('prev.'.$column)
            ->whereColumn('prev.user_id', 'odometer_readings.user_id')
            ->whereColumn('prev.vehicle_id', 'odometer_readings.vehicle_id')
            ->whereNull('prev.deleted_at')
            ->whereRaw(self::earlierThanCurrentSql())
            ->orderByDesc('prev.reading_date')
            ->orderByRaw("COALESCE(prev.reading_time, '00:00:00') DESC")
            ->orderByDesc('prev.id')
            ->limit(1);

        return $query->addSelect([
            'previous_odometer_km' => $previous('odometer_km'),
            'previous_reading_date' => $previous('reading_date'),
        ]);
    }

    /**
     * Distance between this reading and the previous reading of the same vehicle.
     */
    public function getDistanceFromPreviousAttribute(): ?float
    {
        if ($this->previous_odometer_km === null) {
            return null;
        }

        return round((float) $this->odometer_km - (float) $this->previous_odometer_km, 2);
    }

    /**
     * Latest reading recorded for a vehicle.
     */
    public static function latestForVehicle(int $userId, ?int $vehicleId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->when(
                $vehicleId,
                fn (Builder $q) => $q->where('vehicle_id', $vehicleId),
                fn (Builder $q) => $q->whereNull('vehicle_id')
            )
            ->orderByDesc('reading_date')
            ->orderByRaw("COALESCE(reading_time, '00:00:00') DESC")
            ->orderByDesc('id')
            ->first();
    }

    private static function earlierThanCurrentSql(): string
    {
        $prevTime = "COALESCE(prev.reading_time, '00:00:00')";
        $currentTime = "COALESCE(odometer_readings.reading_time, '00:00:00')";

        return '(prev.reading_date < odometer_readings.reading_date'
            ." OR (prev.reading_date = odometer_readings.reading_date AND {$prevTime} < {$currentTime})"
            ." OR (prev.reading_date = odometer_readings.reading_date AND {$prevTime} = {$currentTime} AND prev.id < odometer_readings.id))";
    }
}
