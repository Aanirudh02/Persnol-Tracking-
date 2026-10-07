<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'expense_id',
        'personal_expense_group_id',
        'amount',
        'date',
        'time',
        'description',
        'payment_method',
        'done_by',
        'done_to',
        'notes',
        'is_voluntary',
        'is_archived',
        'classification',
        'credit_debt_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'is_voluntary' => 'boolean',
        'is_archived' => 'boolean',
    ];

    /**
     * The credit this personal expense was filed from.
     */
    public function creditDebt(): BelongsTo
    {
        return $this->belongsTo(CreditDebt::class);
    }

    /**
     * Credit payments that were filed as this personal expense.
     */
    public function creditDebtPayments(): HasMany
    {
        return $this->hasMany(CreditDebtPayment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PersonalExpenseCategory::class, 'category_id');
    }

    public function personalExpenseGroup(): BelongsTo
    {
        return $this->belongsTo(PersonalExpenseGroup::class, 'personal_expense_group_id');
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
