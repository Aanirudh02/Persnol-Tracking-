<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditDebt extends Model
{
    protected $table = 'credit_debts';

    protected $fillable = [
        'user_id',
        'friend_id',
        'type',
        'amount',
        'amount_paid',
        'status',
        'date',
        'location',
        'description',
        'notes',
        'source',
        'food_entry_id',
        'expense_id',
        'friend_transaction_id',
        'due_date',
        'fully_paid_at',
        'settled_discount_amount',
        'is_settled_discounted',
        'linked_expense_id',
        'linked_personal_expense_id',
        'linked_income_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'settled_discount_amount' => 'decimal:2',
        'is_settled_discounted' => 'boolean',
        'date' => 'date',
        'due_date' => 'date',
        'fully_paid_at' => 'datetime',
    ];

    public function linkedExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'linked_expense_id');
    }

    public function linkedPersonalExpense(): BelongsTo
    {
        return $this->belongsTo(PersonalExpense::class, 'linked_personal_expense_id');
    }

    public function linkedIncome(): BelongsTo
    {
        return $this->belongsTo(Income::class, 'linked_income_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(Friend::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CreditDebtPayment::class);
    }

    public function foodEntry(): BelongsTo
    {
        return $this->belongsTo(FoodEntry::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function remaining(): float
    {
        if ($this->is_settled_discounted) {
            return 0.0;
        }

        return max(0, (float) $this->amount - (float) $this->amount_paid - (float) $this->settled_discount_amount);
    }

    public function syncStatusFromPayments(bool $allowManualOverride = true): void
    {
        if ($this->is_settled_discounted) {
            $this->status = 'fully_paid';
            $this->fully_paid_at = $this->fully_paid_at ?? Carbon::now();

            return;
        }

        $paid = (float) $this->amount_paid;
        $total = (float) $this->amount;

        if (in_array($this->status, ['paid_late', 'failed_to_pay'], true) && $allowManualOverride && $paid < $total) {
            return;
        }

        if ($paid <= 0) {
            $this->status = 'yet_to_pay';
            $this->fully_paid_at = null;
        } elseif ($paid < $total) {
            $this->status = 'partially_paid';
            $this->fully_paid_at = null;
        } else {
            $this->status = $this->status === 'paid_late' ? 'paid_late' : 'fully_paid';
            $this->fully_paid_at = $this->fully_paid_at ?? Carbon::now();
        }
    }

    public function statusLabel(): string
    {
        if ($this->is_settled_discounted) {
            return 'Settled (₹'.number_format((float) $this->settled_discount_amount, 2).' forgiven)';
        }

        return match ($this->status) {
            'yet_to_pay' => 'Yet to pay',
            'partially_paid' => 'Partially paid',
            'fully_paid' => 'Fully paid',
            'paid_late' => 'Paid late',
            'failed_to_pay' => 'Failed to pay',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
