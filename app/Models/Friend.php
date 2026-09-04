<?php

namespace App\Models;

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

    /**
     * Calculate financial balance with this friend.
     */
    public function getBalance(): array
    {
        $transactions = $this->transactions()->where('is_settled', false)->get();

        $friendOwesMe = 0.0;
        $iOweFriend = 0.0;

        foreach ($transactions as $tx) {
            if ($tx->type === 'paid_for_friend') {
                $friendOwesMe += (float) $tx->friend_share;
            } elseif ($tx->type === 'friend_paid_for_me') {
                $iOweFriend += (float) $tx->my_share;
            } elseif ($tx->type === 'shared_expense') {
                if ($tx->paid_by_me) {
                    $friendOwesMe += (float) $tx->friend_share;
                } else {
                    $iOweFriend += (float) $tx->my_share;
                }
            }
        }

        $settlements = $this->settlements()->get();
        foreach ($settlements as $st) {
            if ($st->direction === 'i_paid_friend') {
                $iOweFriend -= (float) $st->amount;
            } elseif ($st->direction === 'friend_paid_me') {
                $friendOwesMe -= (float) $st->amount;
            }
        }

        $friendOwesMe = max(0, $friendOwesMe);
        $iOweFriend = max(0, $iOweFriend);
        $net = $friendOwesMe - $iOweFriend;

        return [
            'friend_owes_me' => round($friendOwesMe, 2),
            'i_owe_friend' => round($iOweFriend, 2),
            'net' => round($net, 2),
        ];
    }
}
