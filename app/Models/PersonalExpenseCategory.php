<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalExpenseCategory extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'color',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personalExpenses(): HasMany
    {
        return $this->hasMany(PersonalExpense::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
