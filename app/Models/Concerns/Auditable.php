<?php

namespace App\Models\Concerns;

use App\Services\AuditLogService;

/**
 * Drop onto any master-data model to audit its updates and deletes
 * (rule 10). Before/after carry only the changed attributes. Add
 * `protected static array $auditEvents = ['created', 'updated', 'deleted'];`
 * on a model to also record creations (off by default — bulk imports would
 * flood the stream).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        $events = property_exists(static::class, 'auditEvents') ? static::$auditEvents : ['updated', 'deleted'];
        $prefix = static::auditPrefix();

        if (in_array('created', $events, true)) {
            static::created(function ($model) use ($prefix) {
                app(AuditLogService::class)->record("{$prefix}.created", $model, null, $model->getAttributes());
            });
        }

        if (in_array('updated', $events, true)) {
            static::updating(function ($model) use ($prefix) {
                app(AuditLogService::class)->recordChanges($model, "{$prefix}.updated");
            });
        }

        if (in_array('deleted', $events, true)) {
            static::deleted(function ($model) use ($prefix) {
                app(AuditLogService::class)->record("{$prefix}.deleted", $model, $model->getAttributes(), null);
            });
        }
    }

    public static function auditPrefix(): string
    {
        return \Illuminate\Support\Str::snake(class_basename(static::class));
    }
}
