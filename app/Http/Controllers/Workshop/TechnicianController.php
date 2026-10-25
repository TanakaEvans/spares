<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\JobCard;
use App\Models\JobCardLabour;
use App\Models\Technician;
use App\Models\TechnicianTimeLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TechnicianController extends Controller
{
    use ListQuery;

    public function index(Request $request): Response
    {
        $active = JobCard::whereNotNull('technician_id')
            ->whereIn('status', ['allocated', 'in_progress', 'quality_check'])
            ->selectRaw('technician_id, COUNT(*) as c')->groupBy('technician_id')->pluck('c', 'technician_id');

        return Inertia::render('Workshop/Technicians/Index', [
            'technicians' => $this->listApply(
                Technician::with('branch:id,name'),
                $request,
                [
                    'searchable' => ['name', 'skill_level', 'branch.name'],
                    'sortable' => ['name' => 'name', 'skill_level' => 'skill_level', 'is_active' => 'is_active'],
                    'defaultSort' => ['name' => 'asc'],
                ]
            )->paginate(25)->withQueryString()
                ->through(fn (Technician $t) => [
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
            'filters' => $this->listFilters($request),
        ]);
    }

    public function show(Technician $technician): Response
    {
        $technician->load('branch:id,name');

        $jobs = JobCard::with('customer:id,name', 'vehicle:id,registration', 'invoice:id,document_number')
            ->where('technician_id', $technician->id)
            ->latest('id')->limit(20)->get()
            ->map(fn (JobCard $j) => [
                'id' => $j->id,
                'job_number' => $j->job_number,
                'status' => $j->status,
                'customer' => $j->customer?->name,
                'customer_id' => $j->customer_id,
                'vehicle' => $j->vehicle?->registration,
                'vehicle_id' => $j->vehicle_id,
                'invoice_id' => $j->sales_document_id,
                'invoice_number' => $j->invoice?->document_number,
                'opened_at' => $j->created_at->toDateString(),
                'completed_at' => $j->completed_at?->toDateString(),
            ]);

        $statusCounts = JobCard::where('technician_id', $technician->id)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $timeLogs = TechnicianTimeLog::where('technician_id', $technician->id)
            ->selectRaw('COUNT(*) as entries, COALESCE(SUM(minutes), 0) as minutes, MAX(clock_on) as last_clock_on')
            ->first();

        $hoursBooked = (float) JobCardLabour::where('technician_id', $technician->id)->sum('hours');

        return Inertia::render('Workshop/Technicians/Show', [
            'technician' => [
                'id' => $technician->id,
                'name' => $technician->name,
                'employee_id' => $technician->employee_id,
                'skill_level' => $technician->skill_level,
                'specialisations' => $technician->specialisations,
                'cost_rate' => (float) $technician->cost_rate,
                'is_active' => $technician->is_active,
                'branch' => $technician->branch?->name,
                'branch_id' => $technician->branch_id,
            ],
            'jobs' => $jobs,
            'productivity' => [
                'jobs_total' => (int) $statusCounts->sum(),
                'jobs_completed' => (int) ($statusCounts['completed'] ?? 0) + (int) ($statusCounts['invoiced'] ?? 0) + (int) ($statusCounts['closed'] ?? 0),
                'jobs_active' => (int) ($statusCounts['allocated'] ?? 0) + (int) ($statusCounts['in_progress'] ?? 0) + (int) ($statusCounts['quality_check'] ?? 0),
                'hours_booked' => round($hoursBooked, 2),
                'hours_logged' => round(((int) ($timeLogs->minutes ?? 0)) / 60, 2),
                'time_log_entries' => (int) ($timeLogs->entries ?? 0),
                'last_clock_on' => $timeLogs?->last_clock_on,
            ],
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
