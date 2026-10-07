<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'daily_record_id',
        'category_id',
        'parent_id',
        'expense_group_id',
        'amount',
        'gst_amount',
        'date',
        'time',
        'description',
        'payment_method',
        'paid_by',
        'paid_by_type',
        'paid_by_friend_id',
        'friend_person',
        'split_with_friend_id',
        'split_my_share',
        'split_friend_share',
        'friend_transaction_id',
        'notes',
        'receipt_image',
        'is_locked',
        'is_voluntary',
        'is_archived',
        'classification',
        'credit_debt_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'date' => 'date',
        'is_locked' => 'boolean',
        'is_voluntary' => 'boolean',
        'is_archived' => 'boolean',
        'split_my_share' => 'decimal:2',
        'split_friend_share' => 'decimal:2',
    ];

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
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subItems(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function expenseGroup(): BelongsTo
    {
        return $this->belongsTo(ExpenseGroup::class);
    }

    public function paidByFriend(): BelongsTo
    {
        return $this->belongsTo(Friend::class, 'paid_by_friend_id');
    }

    public function splitWithFriend(): BelongsTo
    {
        return $this->belongsTo(Friend::class, 'split_with_friend_id');
    }

    public function friendSplit(): HasOne
    {
        return $this->hasOne(FriendSplit::class);
    }

    public function friendSplits(): HasMany
    {
        return $this->hasMany(FriendSplit::class);
    }

    /**
     * The credit / debt this expense was filed from (repayment, lending or closing).
     */
    public function creditDebt(): BelongsTo
    {
        return $this->belongsTo(CreditDebt::class);
    }

    /**
     * Credit payments that were filed as this expense.
     */
    public function creditDebtPayments(): HasMany
    {
        return $this->hasMany(CreditDebtPayment::class);
    }

    public function incomeTallies(): HasMany
    {
        return $this->hasMany(IncomeExpenseTally::class);
    }

    public function totalTalliedAmount(): float
    {
        if ($this->relationLoaded('incomeTallies')) {
            return (float) $this->incomeTallies->sum('allocated_amount');
        }

        return (float) $this->incomeTallies()->sum('allocated_amount');
    }

    public function untalliedAmount(): float
    {
        return max(0, $this->totalAmount() - $this->totalTalliedAmount());
    }

    public function isSplit(): bool
    {
        return $this->split_with_friend_id !== null || $this->friendSplits->isNotEmpty() || $this->friendSplit !== null;
    }

    public function totalFriendShare(): float
    {
        if ($this->relationLoaded('friendSplits') && $this->friendSplits->isNotEmpty()) {
            return (float) $this->friendSplits->sum('friend_share');
        }

        return (float) ($this->split_friend_share ?? $this->friendSplit?->friend_share ?? 0);
    }

    public function totalPaidByFriends(): float
    {
        if ($this->relationLoaded('friendSplits') && $this->friendSplits->isNotEmpty()) {
            $sum = (float) $this->friendSplits->sum('paid_by_friend_amount');
            if ($sum <= 0) {
                $friendShareSum = (float) $this->friendSplits->where('paid_by_me_amount', '<=', 0)->sum('friend_share');
                if ($friendShareSum > 0) {
                    return $friendShareSum;
                }
            }

            return $sum;
        }

        if ($this->friendSplit) {
            $paid = (float) $this->friendSplit->paid_by_friend_amount;
            if ($paid <= 0 && (float) $this->friendSplit->friend_share > 0 && (float) $this->friendSplit->paid_by_me_amount <= 0) {
                $paid = (float) $this->friendSplit->friend_share;
            }
            if ($paid > 0) {
                return $paid;
            }
        }

        if ($this->paid_by_type === 'friend' || ($this->paid_by_friend_id && $this->paid_by_type !== 'me') || ($this->paid_by && ! in_array(strtolower(trim($this->paid_by)), ['me', 'self', 'user', '']))) {
            return $this->totalAmount();
        }

        return 0.0;
    }

    public function foodEntries(): HasMany
    {
        return $this->hasMany(FoodEntry::class);
    }

    public function fuelEntry(): HasOne
    {
        return $this->hasOne(FuelEntry::class, 'expense_id');
    }

    public function totalWithChildren(): float
    {
        return $this->totalAmount();
    }

    public function totalAmount(): float
    {
        return round((float) $this->amount + (float) $this->gst_amount, 2);
    }

    /**
     * Split bills saved since the share logic was added store only the user's
     * share in `amount` and record the full bill in a "Total bill:" note.
     */
    public function isAmountNetOfFriendPayments(): bool
    {
        if ($this->notes !== null && str_contains($this->notes, 'Total bill:')) {
            return true;
        }

        $total = $this->totalAmount();

        // Stored amount equals the user's recorded share → already net
        if ((float) $this->split_my_share > 0 && (float) $this->split_friend_share > 0) {
            return abs($total - (float) $this->split_my_share) < 0.01;
        }

        // Stored amount is below the bill recorded on the split → already net
        $splits = $this->relationLoaded('friendSplits') ? $this->friendSplits : $this->friendSplits()->get();
        $billTotal = (float) ($splits->max('total_amount') ?? 0);
        if ($billTotal > 0) {
            return $total < $billTotal - 0.01;
        }

        return false;
    }

    /**
     * What the user actually spent. Net rows already exclude friends' payments;
     * older rows stored the full bill, so friends' payments are subtracted once.
     */
    public function myShareAmount(): float
    {
        if ($this->isAmountNetOfFriendPayments()) {
            return $this->totalAmount();
        }

        return round(max(0, $this->totalAmount() - $this->totalPaidByFriends()), 2);
    }

    public function subItemsExplainedTotal(): float
    {
        return (float) $this->subItems()->get()->sum(fn (self $expense): float => $expense->totalAmount())
            + (float) $this->foodEntries()->get()->sum(fn (FoodEntry $food): float => $food->totalAmount());
    }

    public function consumedAmount(): float
    {
        return $this->subItemsExplainedTotal();
    }

    public function remainingAmount(): float
    {
        return round(max(0, $this->totalAmount() - $this->consumedAmount()), 2);
    }

    public function getTimeAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return strlen($value) > 5 ? substr($value, 0, 5) : $value;
    }

    public function originalBillTotal(): float
    {
        if ($this->notes && preg_match('/Total bill:\s*₹?([0-9,.]+)/i', $this->notes, $matches)) {
            $parsed = (float) str_replace(',', '', $matches[1]);
            if ($parsed > 0) {
                return round($parsed, 2);
            }
        }

        $friendPaid = $this->totalPaidByFriends();
        if ($friendPaid > 0) {
            return round($this->totalAmount() + $friendPaid, 2);
        }

        return $this->totalAmount();
    }

    public function isCombinationPayment(): bool
    {
        if ($this->notes && str_contains($this->notes, 'Total bill:')) {
            return true;
        }

        if ($this->relationLoaded('friendSplits') && $this->friendSplits->isNotEmpty()) {
            $hasPaid = (float) $this->friendSplits->sum('paid_by_friend_amount') > 0;
            $allZeroNet = $this->friendSplits->every(fn (FriendSplit $s): bool => abs($s->netAmount()) < 0.05);

            return $hasPaid && $allZeroNet;
        }

        if ($this->friendSplit) {
            return (float) $this->friendSplit->paid_by_friend_amount > 0 && abs($this->friendSplit->netAmount()) < 0.05;
        }

        return false;
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

        $windowDays = (int) Setting::getVal('expense_edit_window_days', 7);
        if ($windowDays === 0) {
            return false;
        }

        return Carbon::parse($this->created_at)->addDays($windowDays)->isFuture();
    }
}
