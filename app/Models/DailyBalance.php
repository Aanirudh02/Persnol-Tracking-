<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyBalance extends Model
{
    protected $table = 'daily_balances';

    protected $fillable = [
        'user_id',
        'record_date',
        'opening_balance',
        'manual_adjustment',
        'closing_balance',
        'is_opening_manual',
        'is_closing_manual',
        'notes',
        'categories_data',
    ];

    protected $casts = [
        'record_date' => 'date',
        'opening_balance' => 'decimal:2',
        'manual_adjustment' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'is_opening_manual' => 'boolean',
        'is_closing_manual' => 'boolean',
        'categories_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retrieve category breakdown array for a specific category.
     *
     * @return array{opening: float, adjustment: float, closing: float, is_opening_manual: bool, is_closing_manual: bool}
     */
    public function categoryData(string $category): array
    {
        $data = $this->categories_data ?? [];

        return $data[$category] ?? [
            'opening' => 0.0,
            'adjustment' => 0.0,
            'closing' => 0.0,
            'is_opening_manual' => false,
            'is_closing_manual' => false,
        ];
    }
}
