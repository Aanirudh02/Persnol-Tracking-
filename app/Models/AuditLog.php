<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'record_id',
        'action',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logChange(
        string $module,
        int $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?int $userId = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'module' => $module,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'ip_address' => request()->ip(),
        ]);
    }
}
