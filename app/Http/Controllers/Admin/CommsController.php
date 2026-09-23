<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class CommsController extends Controller
{
    private array $keys = ['comms.email_from_name','comms.email_from_address','comms.sms_enabled','comms.sms_sender_id','notifications.sms_monthly_cap','notifications.low_stock_email'];
    public function index(SettingsService $s): Response
    {
        $reg = config('settings_registry');
        return Inertia::render('Admin/Comms/Index', ['settings' => collect($this->keys)->map(fn($k) => ['key' => $k, 'label' => $reg[$k]['label'] ?? $k, 'type' => $reg[$k]['type'] ?? 'string', 'help' => $reg[$k]['help'] ?? null, 'value' => $s->get($k)])->all()]);
    }
    public function update(Request $request, SettingsService $s): RedirectResponse
    {
        foreach ($this->keys as $k) { if ($request->has($k)) { $s->set($k, $request->input($k), null, $request->user()->id); } }
        return back()->with('success', 'Communication settings updated.');
    }
}
