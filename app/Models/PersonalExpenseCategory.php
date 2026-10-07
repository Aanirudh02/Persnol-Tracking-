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

    /**
     * Give a user the starter personal categories (and "Me") once — at sign-up or
     * via migration — instead of re-creating them on every page view.
     */
    public static function ensureDefaultsFor(int $userId): void
    {
        $visible = static::query()->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId));

        if (! (clone $visible)->exists()) {
            foreach ([
                ['name' => 'Me', 'icon' => 'user-check', 'color' => '#6366f1'],
                ['name' => 'Personal', 'icon' => 'user', 'color' => '#3b82f6'],
                ['name' => 'Family', 'icon' => 'heart', 'color' => '#8b5cf6'],
                ['name' => 'Shopping', 'icon' => 'shopping-bag', 'color' => '#ec4899'],
                ['name' => 'Weekend Snacks', 'icon' => 'cookie', 'color' => '#f59e0b'],
                ['name' => 'Tour', 'icon' => 'compass', 'color' => '#06b6d4'],
            ] as $category) {
                static::create(['user_id' => $userId, ...$category]);
            }

            return;
        }

        if (! (clone $visible)->where('name', 'Me')->exists()) {
            static::create(['user_id' => $userId, 'name' => 'Me', 'icon' => 'user-check', 'color' => '#6366f1']);
        }
    }
}
