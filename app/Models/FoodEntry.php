<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodEntry extends Model
{
    protected $fillable = [
        'user_id',
        'daily_record_id',
        'category_id',
        'expense_id',
        'auto_create_expense',
        'item_name',
        'is_snack',
        'quantity',
        'amount',
        'date',
        'time',
        'location',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'is_snack' => 'boolean',
        'auto_create_expense' => 'boolean',
        'quantity' => 'integer',
        'amount' => 'decimal:2',
        'date' => 'date',
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
        return $this->belongsTo(FoodCategory::class, 'category_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
