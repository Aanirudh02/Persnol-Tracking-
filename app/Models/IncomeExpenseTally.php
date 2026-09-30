<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeExpenseTally extends Model
{
    protected $table = 'income_expense_tallies';

    protected $fillable = [
        'user_id',
        'income_id',
        'expense_id',
        'allocated_amount',
        'notes',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
