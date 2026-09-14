<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseStatement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'period_type',
        'start_date',
        'end_date',
        'category_ids',
        'custom_expense_ids',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'category_ids' => 'array',
        'custom_expense_ids' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPersonal(): bool
    {
        return $this->type === 'personal';
    }

    public function isNormal(): bool
    {
        return $this->type === 'normal';
    }
}
