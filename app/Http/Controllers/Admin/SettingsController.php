<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function index(Request $request): Response
    {
        $branchId = $request->integer('branch') ?: null;

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $this->settings->all($branchId),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'editingBranchId' => $branchId,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'values' => ['required', 'array', 'min:1'],
        ]);

        $branchId = $data['branch_id'] ?? null;
        $registry = $this->settings->registry();

        // Validate every submitted key against its declared rules before saving anything.
        foreach ($data['values'] as $key => $value) {
            if (! array_key_exists($key, $registry)) {
                throw ValidationException::withMessages(["values.{$key}" => "Unknown setting [{$key}]."]);
            }

            $definition = $registry[$key];

            if ($branchId !== null && ! ($definition['per_branch'] ?? false)) {
                throw ValidationException::withMessages([
                    "values.{$key}" => "{$definition['label']} cannot be overridden per branch.",
                ]);
            }

            validator(
                ['value' => $value],
                ['value' => $definition['rules'] ?? []],
                [],
                ['value' => $definition['label']]
            )->validate();
        }

        foreach ($data['values'] as $key => $value) {
            $this->settings->set($key, $value, $branchId, $request->user()->id);
        }

        return back()->with('success', count($data['values']).' setting(s) saved.');
    }

    public function revert(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'key' => ['required', 'string'],
        ]);

        $this->settings->revertToGlobal($data['key'], $data['branch_id']);

        return back()->with('success', 'Setting reverted to the global value.');
    }
}
