<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReconciliation extends Model
{
    protected $fillable = [
        'payment_id',
        'user_id',
        'reconciled_date',
        'reconciled_amount',
        'reconciled_by',
        'notes',
    ];

    protected $casts = [
        'reconciled_date' => 'date',
        'reconciled_amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
