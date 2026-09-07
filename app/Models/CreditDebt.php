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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'date' => 'date',
        'due_date' => 'date',
        'fully_paid_at' => 'datetime',
    ];

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
        return max(0, (float) $this->amount - (float) $this->amount_paid);
    }

    public function syncStatusFromPayments(bool $allowManualOverride = true): void
    {
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
