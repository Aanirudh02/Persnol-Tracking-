<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FriendTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'friend_id',
        'type',
        'paid_by_me',
        'total_amount',
        'my_share',
        'friend_share',
        'description',
        'date',
        'payment_method',
        'is_settled',
        'settled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'my_share' => 'decimal:2',
        'friend_share' => 'decimal:2',
        'date' => 'date',
        'is_settled' => 'boolean',
        'paid_by_me' => 'boolean',
        'settled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(Friend::class);
    }
}
