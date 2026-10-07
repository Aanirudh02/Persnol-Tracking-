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
        'expense_id',
        'personal_expense_id',
        'income_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
    ];

    public function creditDebt(): BelongsTo
    {
        return $this->belongsTo(CreditDebt::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function personalExpense(): BelongsTo
    {
        return $this->belongsTo(PersonalExpense::class);
    }

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }
}
