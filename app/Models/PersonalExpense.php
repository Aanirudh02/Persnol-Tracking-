<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'expense_id',
        'amount',
        'date',
        'time',
        'description',
        'payment_method',
        'notes',
        'is_voluntary',
        'is_archived',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'is_voluntary' => 'boolean',
        'is_archived' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PersonalExpenseCategory::class, 'category_id');
    }

    public function linkedExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function totalAmount(): float
    {
        return (float) $this->amount;
    }
}
