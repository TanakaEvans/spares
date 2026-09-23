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
                    'id' => $c->id, 'claim_number' => $c->claim_number, 'supplier' => $c->supplier?->name,
                    'job_number' => $c->jobCard?->job_number, 'part_number' => $c->part?->part_number, 'status' => $c->status,
                    'claim_amount' => (float) $c->claim_amount, 'credit_amount' => (float) $c->credit_amount, 'fault' => $c->fault,
                ]),
            'suppliers' => Supplier::active()->orderBy('name')->get(['id', 'name']),
            'jobs' => JobCard::whereIn('status', ['completed', 'invoiced', 'closed'])->latest('id')->limit(50)->get(['id', 'job_number']),
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
