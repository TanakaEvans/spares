<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Audit Trail (rule 10): searchable, read-only view of every override,
 * approval and master-data change — who, when, before/after.
 */
class AuditLogController extends Controller
{
    use ListQuery;
    public function index(Request $request): Response
    {
        $logs = $this->listApply(
            AuditLog::query()
                ->with('user:id,name')
                ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
                ->when($request->integer('user_id'), fn ($q, $u) => $q->where('user_id', $u)),
            $request,
            [
                'searchable' => ['label', 'note', 'action', 'ip'],
                'sortable' => ['created_at' => 'created_at', 'action' => 'action'],
                'dateColumn' => 'created_at',
                'defaultSort' => ['id' => 'desc'],
            ]
        )
            ->paginate(40)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'at' => $log->created_at->format('d M Y H:i:s'),
                'user' => $log->user?->name ?? 'System',
                'action' => $log->action,
                'entity' => $log->auditable_type ? class_basename($log->auditable_type) : null,
                'entity_id' => $log->auditable_id,
                'label' => $log->label,
                'before' => $log->before,
                'after' => $log->after,
                'note' => $log->note,
                'ip' => $log->ip,
            ]);

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'filters' => $this->listFilters($request, ['action', 'user_id']),
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
