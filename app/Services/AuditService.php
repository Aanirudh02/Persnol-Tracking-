<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public static function log(
        string $module,
        int $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null
    ): AuditLog {
        return AuditLog::logChange(
            module: $module,
            recordId: $recordId,
            action: $action,
            oldValues: $oldValues,
            newValues: $newValues,
            reason: $reason,
            userId: auth()->id()
        );
    }
}
