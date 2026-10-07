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
        'payment_method',
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

    /**
     * Every normal expense filed from this credit/debt (not only the latest one
     * kept in linked_expense_id).
     */
    public function linkedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function linkedPersonalExpenses(): HasMany
    {
        return $this->hasMany(PersonalExpense::class);
    }

    public function linkedIncomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    /**
     * Amount already recorded as normal + personal expenses for this credit/debt.
     * A credit/debt must never be expensed for more than its amount.
     */
    public function expensedAmount(): float
    {
        $normal = (float) $this->linkedExpenses()->get()->sum(fn (Expense $expense) => $expense->totalAmount());
        $personal = (float) $this->linkedPersonalExpenses()->sum('amount');

        return round($normal + $personal, 2);
    }

    /**
     * Amount already recorded as income for this debt.
     */
    public function incomeRecordedAmount(): float
    {
        return round((float) $this->linkedIncomes()->sum('amount'), 2);
    }

    /**
     * How much may still be filed as expense without double counting.
     */
    public function unexpensedAmount(): float
    {
        return round(max(0, (float) $this->amount - (float) $this->settled_discount_amount - $this->expensedAmount()), 2);
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

    /**
     * Recompute amount_paid from the payment rows and update the status.
     */
    public function refreshPaidAmount(bool $allowManualOverride = true): void
    {
        $this->amount_paid = (float) $this->payments()->sum('amount');
        $this->syncStatusFromPayments($allowManualOverride);
        $this->save();
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
            if ((float) $this->amount_paid <= 0) {
                return 'Settled No-Pay (₹'.number_format((float) $this->settled_discount_amount, 2).' waived)';
            }

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
