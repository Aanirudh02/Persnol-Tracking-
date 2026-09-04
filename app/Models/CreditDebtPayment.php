<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditDebtPayment extends Model
{
    protected $fillable = [
        'credit_debt_id',
        'amount',
        'paid_on',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
    ];

    public function creditDebt(): BelongsTo
    {
        return $this->belongsTo(CreditDebt::class);
    }
}
