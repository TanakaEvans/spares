<?php

namespace App\Http\Controllers\Workshop;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\LabourCode;
use App\Models\Part;
use App\Models\StockLevel;
use App\Models\Technician;
use App\Services\JobCardService;
use App\Services\WorkshopInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobCardController extends Controller
{
    public function __construct(private readonly JobCardService $jobs)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Workshop/Jobs/Index', [
            'jobs' => JobCard::with('customer:id,name', 'vehicle:id,registration', 'technician:id,name')
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('job_number', 'like', "%{$s}%")
                        ->orWhereHas('vehicle', fn ($y) => $y->where('registration', 'like', "%{$s}%"))
                        ->orWhereHas('customer', fn ($y) => $y->where('name', 'like', "%{$s}%"))
                ))
                ->latest('id')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (JobCard $j) => [
                    'id' => $j->id,
                    'job_number' => $j->job_number,
                    'customer' => $j->customer?->name,
                    'customer_id' => $j->customer_id,
                    'vehicle' => $j->vehicle?->registration,
                    'vehicle_id' => $j->vehicle_id,
                    'technician' => $j->technician?->name,
                    'status' => $j->status,
                    'created_at' => $j->created_at->toDateString(),
                ]),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Workshop/Jobs/Create', [
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
            'preselectedVehicleId' => $request->integer('vehicle_id') ?: null,
        ]);
    }

    /** Vehicle search by registration → resolves the owning customer too. */
    public function vehicleLookup(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['vehicles' => []]);
        }

        $vehicles = CustomerVehicle::with('customer:id,name', 'make:id,name', 'model:id,name')
            ->where(fn ($x) => $x->where('registration', 'like', "%{$q}%")
                ->orWhereHas('customer', fn ($y) => $y->where('name', 'like', "%{$q}%")))
            ->orderBy('registration')->limit(8)->get()
            ->map(fn (CustomerVehicle $v) => [
                'id' => $v->id,
                'registration' => $v->registration,
                'label' => $v->label(),
                'customer_id' => $v->customer_id,
                'customer' => $v->customer?->name,
            ]);

        return response()->json(['vehicles' => $vehicles]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'vehicle_id' => ['nullable', 'exists:customer_vehicles,id'],
            'reported_fault' => ['nullable', 'string', 'max:2000'],
            'odometer_in' => ['nullable', 'integer', 'min:0'],
            'promised_at' => ['nullable', 'date'],
            'authorisation_required' => ['boolean'],
        ]);

        $job = $this->jobs->create($data['branch_id'], $data, $request->user()->id);

        return redirect()->route('workshop.jobs.show', $job)
            ->with('success', "Job {$job->job_number} opened.");
    }

    public function show(JobCard $job): Response
    {
        $job->load('customer:id,name,customer_number,is_walk_in', 'vehicle', 'technician:id,name',
            'labours.labourCode:id,code', 'labours.technician:id,name', 'parts.part:id,part_number',
            'invoice:id,document_number');

        return Inertia::render('Workshop/Jobs/Show', [
            'job' => [
                'id' => $job->id,
                'job_number' => $job->job_number,
                'status' => $job->status,
                'branch_id' => $job->branch_id,
                'customer' => $job->customer?->only(['id', 'name', 'customer_number', 'is_walk_in']),
                'vehicle' => $job->vehicle ? [
                    'id' => $job->vehicle->id, 'registration' => $job->vehicle->registration,
                    'label' => $job->vehicle->label(),
                ] : null,
                'technician' => $job->technician?->only(['id', 'name']),
                'reported_fault' => $job->reported_fault,
                'work_done' => $job->work_done,
                'odometer_in' => $job->odometer_in,
                'odometer_out' => $job->odometer_out,
                'promised_at' => $job->promised_at?->toDateTimeString(),
                'invoice' => $job->invoice?->only(['id', 'document_number']),
                'next_statuses' => JobCard::FLOW[$job->status] ?? [],
                'is_invoiceable' => $job->status === 'completed' && $job->customer_id,
                'labours' => $job->labours->map(fn ($l) => [
                    'id' => $l->id, 'description' => $l->description, 'code' => $l->labourCode?->code,
                    'technician' => $l->technician?->name, 'hours' => (float) $l->hours, 'rate' => (float) $l->rate,
                    'line_total' => (float) $l->line_total, 'is_warranty' => $l->is_warranty,
                ]),
                'parts' => $job->parts->map(fn ($p) => [
                    'id' => $p->id, 'part_id' => $p->part_id, 'part_number' => $p->part?->part_number,
                    'description' => $p->description, 'qty' => (float) $p->qty, 'unit_price' => (float) $p->unit_price,
                    'status' => $p->status, 'is_warranty' => $p->is_warranty,
                ]),
                'costing' => [
                    'labour_billed' => $job->labourBilled(), 'parts_billed' => $job->partsBilled(),
                    'labour_cost' => $job->labourCost(), 'parts_cost' => $job->partsCost(),
                    'total_billed' => $job->totalBilled(), 'total_cost' => $job->totalCost(),
                    'margin_pct' => $job->marginPct(),
                ],
            ],
            'labourCodes' => LabourCode::active()->orderBy('code')->get(['id', 'code', 'description', 'standard_hours', 'default_rate']),
            'technicians' => Technician::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function transition(Request $request, JobCard $job): RedirectResponse
    {
        $to = $request->validate(['status' => ['required', 'string']])['status'];
        try {
            $this->jobs->transition($job, $to, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', "Job moved to ".str_replace('_', ' ', $to).'.');
    }

    public function update(Request $request, JobCard $job): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'vehicle_id' => ['nullable', 'exists:customer_vehicles,id'],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'work_done' => ['nullable', 'string', 'max:2000'],
            'odometer_out' => ['nullable', 'integer', 'min:0'],
        ]);

        if (array_key_exists('technician_id', $data) && $data['technician_id']) {
            $this->jobs->assignTechnician($job, $data['technician_id']);
        }
        $job->update(array_filter($data, fn ($v, $k) => $k !== 'technician_id', ARRAY_FILTER_USE_BOTH));

        return back()->with('success', 'Job updated.');
    }

    // ── Labour ───────────────────────────────────────────────────────────

    public function addLabour(Request $request, JobCard $job): RedirectResponse
    {
        $data = $request->validate([
            'labour_code_id' => ['nullable', 'exists:labour_codes,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'hours' => ['required', 'numeric', 'gte:0'],
            'rate' => ['nullable', 'numeric', 'gte:0'],
            'technician_id' => ['nullable', 'exists:technicians,id'],
            'is_warranty' => ['boolean'],
        ]);
        $this->jobs->addLabour($job, $data);

        return back()->with('success', 'Labour added.');
    }

    public function removeLabour(JobCard $job, int $labour): RedirectResponse
    {
        $this->jobs->removeLabour($job, $labour);

        return back()->with('success', 'Labour removed.');
    }

    // ── Parts ────────────────────────────────────────────────────────────

    public function partLookup(Request $request, \App\Services\PricingService $pricing): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $branchId = $request->integer('branch_id') ?: null;
        if (mb_strlen($q) < 2) {
            return response()->json(['parts' => []]);
        }

        $parts = Part::search($q)->active()->limit(8)->get()->map(function (Part $p) use ($branchId, $pricing) {
            $level = $branchId ? StockLevel::where('part_id', $p->id)->where('branch_id', $branchId)->first() : null;
            try {
                $price = round($pricing->priceFor($p), 2); // default retail list
            } catch (\App\Exceptions\UnpricedPartException) {
                $price = 0.0;
            }

            return [
                'id' => $p->id, 'part_number' => $p->part_number, 'description' => $p->description,
                'on_hand' => (float) ($level->qty_on_hand ?? 0),
                'avco' => (float) ($level->average_cost ?? 0),
                'price' => $price,
            ];
        });

        return response()->json(['parts' => $parts]);
    }

    public function requestPart(Request $request, JobCard $job): RedirectResponse
    {
        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'gte:0'],
            'is_warranty' => ['boolean'],
        ]);
        $this->jobs->requestPart($job, $data);

        return back()->with('success', 'Part added to the job.');
    }

    public function issuePart(Request $request, JobCard $job, JobCardPart $part): RedirectResponse
    {
        abort_unless($part->job_card_id === $job->id, 404);
        try {
            $this->jobs->issuePart($part, $request->user()->id);
        } catch (DomainException $e) {
            return back()->withErrors(['part' => $e->userMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['part' => $e->getMessage()]);
        }

        return back()->with('success', 'Part issued from stock.');
    }

    public function returnPart(Request $request, JobCard $job, JobCardPart $part): RedirectResponse
    {
        abort_unless($part->job_card_id === $job->id, 404);
        try {
            $this->jobs->returnPart($part, $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['part' => $e->getMessage()]);
        }

        return back()->with('success', 'Part returned to stock.');
    }

    // ── Invoicing ────────────────────────────────────────────────────────

    public function invoice(Request $request, JobCard $job, WorkshopInvoiceService $invoicing): RedirectResponse
    {
        $data = $request->validate([
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required', 'in:cash,card,eft,account'],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.tendered' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $invoice = $invoicing->invoiceJob($job, $data['payments'] ?? [], $request->user()->id);
        } catch (DomainException $e) {
            return back()->withErrors(['invoice' => $e->userMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }

        return redirect()->route('sales.invoices.show', $invoice)
            ->with('success', "Job {$job->job_number} invoiced as {$invoice->document_number}.");
    }
}
