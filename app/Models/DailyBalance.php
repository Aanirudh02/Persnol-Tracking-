<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyBalance extends Model
{
    protected $table = 'daily_balances';

    protected $fillable = [
        'user_id',
        'record_date',
        'opening_balance',
        'manual_adjustment',
        'closing_balance',
        'is_opening_manual',
        'is_closing_manual',
        'notes',
    ];

    protected $casts = [
        'record_date' => 'date',
        'opening_balance' => 'decimal:2',
        'manual_adjustment' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'is_opening_manual' => 'boolean',
        'is_closing_manual' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
