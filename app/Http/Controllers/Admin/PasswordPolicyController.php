<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class PasswordPolicyController extends Controller
{
    private array $keys = ['security.password_min_length','security.password_require_mixed_case','security.password_require_number','security.password_expiry_days','security.max_failed_attempts','security.lockout_minutes'];
    public function index(SettingsService $s): Response
    {
        $reg = config('settings_registry');
        return Inertia::render('Admin/PasswordPolicy/Index', ['settings' => collect($this->keys)->map(fn($k) => ['key' => $k, 'label' => $reg[$k]['label'] ?? $k, 'type' => $reg[$k]['type'] ?? 'string', 'help' => $reg[$k]['help'] ?? null, 'value' => $s->get($k)])->all()]);
    }
    public function update(Request $request, SettingsService $s): RedirectResponse
    {
        foreach ($this->keys as $k) { if ($request->has($k)) { $s->set($k, $request->input($k), null, $request->user()->id); } }
        return back()->with('success', 'Password policy updated.');
    }
}
