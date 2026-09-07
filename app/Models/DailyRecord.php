<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyRecord extends Model
{
    protected $fillable = [
        'user_id',
        'record_date',
        'wake_up_time',
        'sleep_time',
        'sleep_quality',
        'day_rating',
        'sleep_duration_hours',
        'sleep_notes',
        'day_summary',
        'wake_up_prompt_dismissed',
        'sleep_prompt_dismissed',
    ];

    protected $casts = [
        'record_date' => 'date',
        'sleep_quality' => 'integer',
        'day_rating' => 'integer',
        'sleep_duration_hours' => 'float',
        'wake_up_prompt_dismissed' => 'boolean',
        'sleep_prompt_dismissed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function foodEntries(): HasMany
    {
        return $this->hasMany(FoodEntry::class);
    }

    public function scooterTrips(): HasMany
    {
        return $this->hasMany(ScooterTrip::class);
    }

    public function fuelEntries(): HasMany
    {
        return $this->hasMany(FuelEntry::class);
    }

    public function mistakes(): HasMany
    {
        return $this->hasMany(Mistake::class);
    }

    /**
     * Calculate sleep duration if both times available (or from previous day's sleep)
     */
    public function calculateSleepDuration(): void
    {
        if ($this->wake_up_time) {
            // Check previous day record for sleep time
            $prevRecord = self::where('user_id', $this->user_id)
                ->where('record_date', Carbon::parse($this->record_date)->subDay()->toDateString())
                ->first();

            if ($prevRecord && $prevRecord->sleep_time) {
                $sleepDateTime = Carbon::parse($prevRecord->record_date->toDateString().' '.$prevRecord->sleep_time);
                $wakeDateTime = Carbon::parse($this->record_date->toDateString().' '.$this->wake_up_time);
                if ($wakeDateTime->gt($sleepDateTime)) {
                    $this->sleep_duration_hours = round($wakeDateTime->diffInMinutes($sleepDateTime) / 60, 2);
                    $this->save();
                }
            }
        }
    }
}
