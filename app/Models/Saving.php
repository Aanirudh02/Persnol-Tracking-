<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Saving extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'source',
        'goal_or_category',
        'saved_date',
        'notes',
        'status',
        'withdrawn_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'withdrawn_amount' => 'decimal:2',
        'saved_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function netAvailable(): float
    {
        return max(0, (float) $this->amount - (float) $this->withdrawn_amount);
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            'withdrawn' => 'bg-rose-100 text-rose-800 border-rose-200',
            'locked' => 'bg-amber-100 text-amber-800 border-amber-200',
            default => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        };
    }
}
