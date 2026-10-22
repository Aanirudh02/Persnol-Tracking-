<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GPayAudit extends Model
{
    protected $table = 'gpay_audits';

    protected $fillable = [
        'user_id',
        'screenshot_path',
        'month_year',
        'raw_text',
        'extracted_items',
        'audit_results',
        'gpay_balance',
        'status',
    ];

    protected $casts = [
        'extracted_items' => 'array',
        'audit_results' => 'array',
        'gpay_balance' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
