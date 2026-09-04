<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomeCategory extends Model
{
    protected $fillable = ['user_id', 'name', 'icon', 'color', 'is_archived'];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
