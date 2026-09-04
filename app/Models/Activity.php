<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = [
        'user_id',
        'daily_record_id',
        'category_id',
        'title',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'location',
        'description',
        'notes',
        'image_path',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ActivityCategory::class, 'category_id');
    }
}
