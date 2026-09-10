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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'date' => 'date',
        'is_locked' => 'boolean',
        'is_voluntary' => 'boolean',
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
            return (float) $this->friendSplits->sum('paid_by_friend_amount');
        }

        return (float) ($this->friendSplit?->paid_by_friend_amount ?? ($this->paid_by_type === 'friend' ? $this->totalAmount() : 0));
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
