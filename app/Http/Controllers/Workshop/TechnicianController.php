<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\JobCard;
use App\Models\Technician;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianController extends Controller
{
    public function index(): Response
    {
        $active = JobCard::whereNotNull('technician_id')
            ->whereIn('status', ['allocated', 'in_progress', 'quality_check'])
            ->selectRaw('technician_id, COUNT(*) as c')->groupBy('technician_id')->pluck('c', 'technician_id');

        return Inertia::render('Workshop/Technicians/Index', [
            'technicians' => Technician::with('branch:id,name')->orderBy('name')->get()
                ->map(fn (Technician $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'skill_level' => $t->skill_level,
                    'specialisations' => $t->specialisations,
                    'cost_rate' => (float) $t->cost_rate,
                    'branch' => $t->branch?->name,
                    'is_active' => $t->is_active,
                    'active_jobs' => (int) ($active[$t->id] ?? 0),
                ]),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'skill_level' => ['required', 'in:apprentice,qualified,master'],
            'specialisations' => ['nullable', 'string', 'max:255'],
            'cost_rate' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ]);
        Technician::create($data + ['is_active' => true]);

        return back()->with('success', "Technician {$data['name']} added.");
    }

    public function update(Request $request, Technician $technician): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'skill_level' => ['required', 'in:apprentice,qualified,master'],
            'specialisations' => ['nullable', 'string', 'max:255'],
            'cost_rate' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['boolean'],
        ]);
        $technician->update($data);

        return back()->with('success', "{$technician->name} updated.");
    }
}
