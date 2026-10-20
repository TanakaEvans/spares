<?php

namespace App\Http\Controllers\Admin;

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
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'action' => (string) $request->query('action', ''),
            'user_id' => $request->integer('user_id') ?: null,
            'from' => (string) $request->query('from', ''),
            'to' => (string) $request->query('to', ''),
        ];

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($filters['action'], fn ($q, $a) => $q->where('action', $a))
            ->when($filters['user_id'], fn ($q, $u) => $q->where('user_id', $u))
            ->when($filters['from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->when($filters['search'], fn ($q, $s) => $q->where(fn ($x) => $x
                ->where('label', 'like', "%{$s}%")
                ->orWhere('note', 'like', "%{$s}%")
                ->orWhere('action', 'like', "%{$s}%")))
            ->orderByDesc('id')
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
            'filters' => $filters,
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
