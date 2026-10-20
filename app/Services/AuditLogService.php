<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * The single writer for `audit_logs` (rule 10). Call it for every override,
 * approval and setting/master-data change; the Auditable trait calls it for
 * model updates/deletes automatically.
 *
 * Writing an audit row must never break the business action it describes, so
 * failures are logged (audit.write_failed) rather than thrown.
 */
class AuditLogService
{
    /** Attributes never written to the audit stream. */
    public const REDACTED = ['password', 'remember_token', 'two_factor_secret'];

    public function record(
        string $action,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?string $note = null,
        ?int $userId = null,
        ?int $branchId = null,
    ): ?AuditLog {
        try {
            return AuditLog::create([
                'user_id' => $userId ?? Auth::id(),
                'branch_id' => $branchId,
                'action' => $action,
                'auditable_type' => $subject ? $subject::class : null,
                'auditable_id' => $subject?->getKey(),
                'label' => $subject ? self::labelFor($subject) : null,
                'before' => $before !== null ? self::redact($before) : null,
                'after' => $after !== null ? self::redact($after) : null,
                'note' => $note !== null ? mb_substr($note, 0, 255) : null,
                'ip' => request()?->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('audit.write_failed', ['action' => $action, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /** Record the dirty attributes of a model that is about to be saved. */
    public function recordChanges(Model $model, string $action, ?string $note = null): ?AuditLog
    {
        $before = [];
        $after = [];
        foreach ($model->getDirty() as $key => $value) {
            if (in_array($key, ['updated_at', 'created_at'], true)) {
                continue;
            }
            $before[$key] = $model->getOriginal($key);
            $after[$key] = $value;
        }

        if ($after === []) {
            return null;
        }

        return $this->record($action, $model, $before, $after, $note);
    }

    public static function labelFor(Model $model): ?string
    {
        foreach (['name', 'part_number', 'document_number', 'journal_number', 'username', 'key', 'customer_number', 'supplier_number', 'code'] as $attr) {
            $value = $model->getAttribute($attr);
            if ($value !== null && $value !== '') {
                return mb_substr((string) $value, 0, 150);
            }
        }

        return null;
    }

    private static function redact(array $values): array
    {
        foreach (self::REDACTED as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = '••••••';
            }
        }

        return $values;
    }
}
