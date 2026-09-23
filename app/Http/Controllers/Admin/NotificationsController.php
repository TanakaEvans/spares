<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
class NotificationsController extends Controller
{
    public function index(Request $request): Response
    {
        $events = collect(config('notification_events', []))->map(fn($e, $k) => [
            'key' => $k, 'label' => $e['label'] ?? $k, 'group' => $e['group'] ?? 'General',
            'channels' => $e['channels'] ?? ($e['default_channels'] ?? []),
        ])->values();
        $recent = DB::table('notifications')->orderByDesc('created_at')->limit(30)->get()->map(function ($n) {
            $data = json_decode($n->data, true) ?? [];
            return ['id' => $n->id, 'type' => class_basename($n->type), 'message' => $data['message'] ?? ($data['title'] ?? $n->type),
                'read' => $n->read_at !== null, 'at' => $n->created_at];
        });
        return Inertia::render('Admin/Notifications/Index', ['events' => $events, 'recent' => $recent]);
    }
}
