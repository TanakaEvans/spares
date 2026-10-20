<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Supplier;
use App\Models\WarrantyClaim;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarrantyClaimController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Workshop/Warranty/Index', [
            'claims' => WarrantyClaim::with('supplier:id,name', 'jobCard:id,job_number', 'part:id,part_number')
                ->latest('id')->paginate(20)
                ->through(fn (WarrantyClaim $c) => [
                    'id' => $c->id, 'claim_number' => $c->claim_number,
                    'supplier' => $c->supplier?->name, 'supplier_id' => $c->supplier_id,
                    'job_number' => $c->jobCard?->job_number, 'job_card_id' => $c->job_card_id,
                    'part_number' => $c->part?->part_number, 'part_id' => $c->part_id, 'status' => $c->status,
                    'claim_amount' => (float) $c->claim_amount, 'credit_amount' => (float) $c->credit_amount, 'fault' => $c->fault,
                ]),
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
            'jobs' => JobCard::whereIn('status', ['completed', 'invoiced', 'closed'])->latest('id')->limit(50)->get(['id', 'job_number']),
        ]);
    }

    public function show(WarrantyClaim $claim): Response
    {
        $claim->load('supplier:id,name', 'part:id,part_number,description', 'jobCard:id,job_number,status,customer_id,vehicle_id,sales_document_id',
            'jobCard.customer:id,name', 'jobCard.vehicle:id,registration', 'jobCard.invoice:id,document_number');

        return Inertia::render('Workshop/Warranty/Show', [
            'claim' => [
                'id' => $claim->id,
                'claim_number' => $claim->claim_number,
                'status' => $claim->status,
                'fault' => $claim->fault,
                'claim_amount' => (float) $claim->claim_amount,
                'credit_amount' => (float) $claim->credit_amount,
                'supplier_ref' => $claim->supplier_ref,
                'created_at' => $claim->created_at?->toDateString(),
                'updated_at' => $claim->updated_at?->toDateTimeString(),
                'supplier' => $claim->supplier?->name,
                'supplier_id' => $claim->supplier_id,
                'part_number' => $claim->part?->part_number,
                'part_description' => $claim->part?->description,
                'part_id' => $claim->part_id,
                'job_number' => $claim->jobCard?->job_number,
                'job_card_id' => $claim->job_card_id,
                'job_status' => $claim->jobCard?->status,
                'customer' => $claim->jobCard?->customer?->name,
                'customer_id' => $claim->jobCard?->customer_id,
                'vehicle' => $claim->jobCard?->vehicle?->registration,
                'vehicle_id' => $claim->jobCard?->vehicle_id,
                'invoice_number' => $claim->jobCard?->invoice?->document_number,
                'invoice_id' => $claim->jobCard?->sales_document_id,
            ],
        ]);
    }

    public function store(Request $request, NumberSequenceService $seq): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'], 'job_card_id' => ['nullable', 'exists:job_cards,id'],
            'fault' => ['required', 'string', 'max:1000'], 'claim_amount' => ['required', 'numeric', 'min:0'],
        ]);
        WarrantyClaim::create($data + ['claim_number' => $seq->next('warranty_claim'), 'status' => 'draft']);

        return back()->with('success', 'Warranty claim raised.');
    }

    public function transition(Request $request, WarrantyClaim $warrantyClaim): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:submitted,acknowledged,approved,rejected,credited'],
            'credit_amount' => ['nullable', 'numeric', 'min:0'],
            'supplier_ref' => ['nullable', 'string', 'max:60'],
        ]);
        $warrantyClaim->update([
            'status' => $data['status'],
            'credit_amount' => $data['status'] === 'credited' ? ($data['credit_amount'] ?? $warrantyClaim->claim_amount) : $warrantyClaim->credit_amount,
            'supplier_ref' => $data['supplier_ref'] ?? $warrantyClaim->supplier_ref,
        ]);

        return back()->with('success', "Claim {$warrantyClaim->claim_number} marked {$warrantyClaim->status}.");
    }
}
