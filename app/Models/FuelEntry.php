<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FuelEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'daily_record_id',
        'date',
        'time',
        'amount',
        'litres',
        'price_per_litre',
        'odometer',
        'petrol_station',
        'payment_method',
        'notes',
        'receipt_image',
        'is_locked',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'litres' => 'decimal:2',
        'price_per_litre' => 'decimal:2',
        'odometer' => 'integer',
        'date' => 'date',
        'is_locked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isEditableByUser(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;
        if ($user->isAdmin()) return true;
        if ($this->is_locked) return false;

        $windowDays = (int) Setting::getVal('petrol_edit_window_days', 7);
        if ($windowDays === 0) return false;

        return Carbon::parse($this->created_at)->addDays($windowDays)->isFuture();
    }
}
