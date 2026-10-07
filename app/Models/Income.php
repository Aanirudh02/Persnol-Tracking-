<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'daily_record_id',
        'category_id',
        'amount',
        'source',
        'date',
        'time',
        'payment_method',
        'tally_mode',
        'description',
        'notes',
        'is_locked',
        'credit_debt_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'is_locked' => 'boolean',
    ];

    /**
     * The debt this income was recovered from.
     */
    public function creditDebt(): BelongsTo
    {
        return $this->belongsTo(CreditDebt::class);
    }

    /**
     * Debt payments that were recorded as this income.
     */
    public function creditDebtPayments(): HasMany
    {
        return $this->hasMany(CreditDebtPayment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyRecord(): BelongsTo
    {
        return $this->belongsTo(DailyRecord::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'category_id');
    }

    public function tallies(): HasMany
    {
        return $this->hasMany(IncomeExpenseTally::class);
    }

    public function talliedAmount(): float
    {
        return (float) $this->tallies()->sum('allocated_amount');
    }

    public function untalliedAmount(): float
    {
        return max(0, (float) $this->amount - $this->talliedAmount());
    }

    public function isEditableByUser(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if ($this->is_locked) {
            return false;
        }

        $windowDays = (int) Setting::getVal('income_edit_window_days', 7);
        if ($windowDays === 0) {
            return false;
        }

        return Carbon::parse($this->created_at)->addDays($windowDays)->isFuture();
    }
}
