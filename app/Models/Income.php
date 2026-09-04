<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
        'description',
        'notes',
        'is_locked',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'is_locked' => 'boolean',
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
        return $this->belongsTo(IncomeCategory::class, 'category_id');
    }

    public function isEditableByUser(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;
        if ($user->isAdmin()) return true;
        if ($this->is_locked) return false;

        $windowDays = (int) Setting::getVal('income_edit_window_days', 7);
        if ($windowDays === 0) return false;

        return Carbon::parse($this->created_at)->addDays($windowDays)->isFuture();
    }
}
