<?php

namespace App\Models;

use App\Services\FriendBalanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Friend extends Model
{
    protected $fillable = ['user_id', 'name', 'role', 'phone', 'email', 'date_of_birth', 'notes'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function isFriend(): bool
    {
        return ($this->role ?? 'Friend') === 'Friend';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FriendTransaction::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function creditDebts(): HasMany
    {
        return $this->hasMany(CreditDebt::class);
    }

    public function friendSplits(): HasMany
    {
        return $this->hasMany(FriendSplit::class);
    }

    /**
     * Calculate financial balance with this friend.
     */
    public function getBalance(): array
    {
        return app(FriendBalanceService::class)->forFriend($this);
    }
}
