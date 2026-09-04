<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $fillable = ['user_id', 'name', 'icon', 'color', 'is_archived', 'is_voluntary'];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_voluntary' => 'boolean',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
