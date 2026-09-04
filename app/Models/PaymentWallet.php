<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWallet extends Model
{
    protected $fillable = [
        'user_id',
        'payment_method',
        'is_enabled',
        'opening_balance',
        'opening_as_of',
        'notes',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'opening_balance' => 'decimal:2',
        'opening_as_of' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
