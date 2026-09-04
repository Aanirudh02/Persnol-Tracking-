<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'date',
        'time',
        'paid_by',
        'paid_to',
        'purpose',
        'category',
        'payment_method',
        'reference',
        'status',
        'notes',
        'is_locked',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'is_locked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reconciliation(): HasOne
    {
        return $this->hasOne(PaymentReconciliation::class);
    }

    public function isEditableByUser(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;
        if ($user->isAdmin()) return true;
        if ($this->is_locked || $this->status === 'Reconciled') return false;

        $windowDays = (int) Setting::getVal('payment_edit_window_days', 7);
        if ($windowDays === 0) return false;

        return Carbon::parse($this->created_at)->addDays($windowDays)->isFuture();
    }
}
