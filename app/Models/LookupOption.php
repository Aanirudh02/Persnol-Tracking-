<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class LookupOption extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'icon',
        'color',
        'sort_order',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function clearCache(?int $userId = null, ?string $type = null): void
    {
        if ($userId && $type) {
            Cache::forget("lookup_options:{$userId}:{$type}");
        }
    }
}
