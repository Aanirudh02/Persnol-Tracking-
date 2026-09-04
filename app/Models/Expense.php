<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'daily_record_id',
        'category_id',
        'parent_id',
        'amount',
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

    public function paidByFriend(): BelongsTo
    {
        return $this->belongsTo(Friend::class, 'paid_by_friend_id');
    }

    public function splitWithFriend(): BelongsTo
    {
        return $this->belongsTo(Friend::class, 'split_with_friend_id');
    }

    public function foodEntries(): HasMany
    {
        return $this->hasMany(FoodEntry::class);
    }

    public function totalWithChildren(): float
    {
        // Parent amount is cash truth; sub-items are breakdown only.
        return (float) $this->amount;
    }

    public function subItemsExplainedTotal(): float
    {
        return (float) $this->subItems()->sum('amount') + (float) $this->foodEntries()->sum('amount');
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
