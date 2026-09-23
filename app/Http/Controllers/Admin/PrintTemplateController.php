<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class PrintTemplateController extends Controller
{
    private array $keys = ['documents.invoice_footer_text','documents.quote_terms_text','documents.receipt_copies'];
    public function index(SettingsService $s): Response
    {
        $reg = config('settings_registry');
        return Inertia::render('Admin/PrintTemplates/Index', ['settings' => collect($this->keys)->map(fn($k) => ['key' => $k, 'label' => $reg[$k]['label'] ?? $k, 'type' => $reg[$k]['type'] ?? 'string', 'help' => $reg[$k]['help'] ?? null, 'value' => $s->get($k)])->all()]);
    }
    public function update(Request $request, SettingsService $s): RedirectResponse
    {
        foreach ($this->keys as $k) { if ($request->has($k)) { $s->set($k, $request->input($k), null, $request->user()->id); } }
        return back()->with('success', 'Document templates updated.');
    }
}
