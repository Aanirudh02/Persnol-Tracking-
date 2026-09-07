<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FriendSplit extends Model
{
    protected $fillable = [
        'user_id',
        'friend_id',
        'expense_id',
        'legacy_friend_transaction_id',
        'description',
        'date',
        'payment_method',
        'total_amount',
        'my_share',
        'friend_share',
        'paid_by_me_amount',
        'paid_by_friend_amount',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'my_share' => 'decimal:2',
        'friend_share' => 'decimal:2',
        'paid_by_me_amount' => 'decimal:2',
        'paid_by_friend_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(Friend::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function netAmount(): float
    {
        return round((float) $this->paid_by_me_amount - (float) $this->my_share, 2);
    }

    public function paymentMode(): string
    {
        $paidByMe = round((float) $this->paid_by_me_amount, 2);
        $paidByFriend = round((float) $this->paid_by_friend_amount, 2);

        if ($paidByMe > 0 && $paidByFriend > 0) {
            return 'split';
        }

        return $paidByMe > 0 ? 'me' : 'friend';
    }
}
