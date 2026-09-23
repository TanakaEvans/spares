<?php

namespace App\Services;

use App\Models\JobCard;
use App\Models\JobCardPart;
use App\Models\LabourCode;
use App\Models\Part;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Job card lifecycle, labour and parts requisition (Modules 4.1/4.4).
 * Parts leave stock at ISSUE (JOB_CARD_OUT) with COGS booked then; the
 * invoice later recognises revenue only (WorkshopInvoiceService).
 */
class JobCardService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly StockLedgerService $stock,
        private readonly GlPostingService $gl,
    ) {
    }

    public function create(int $branchId, array $data, ?int $userId = null): JobCard
    {
        return DB::transaction(function () use ($branchId, $data, $userId) {
            return JobCard::create([
                'job_number' => $this->sequences->next('job_card', $branchId),
                'branch_id' => $branchId,
                'status' => 'open',
                'customer_id' => $data['customer_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'reported_fault' => $data['reported_fault'] ?? null,
                'odometer_in' => $data['odometer_in'] ?? null,
                'promised_at' => $data['promised_at'] ?? null,
                'authorisation_required' => $data['authorisation_required'] ?? false,
                'opened_by' => $userId,
            ]);
        });
    }

    /** Move through the status machine (JobCard::FLOW). */
    public function transition(JobCard $job, string $to, ?int $userId = null): JobCard
    {
        if (! $job->canTransitionTo($to)) {
            throw new InvalidArgumentException("Cannot move job {$job->job_number} from {$job->status} to {$to}.");
        }

        // A job may not leave 'open' without a customer and vehicle.
        if ($job->status === 'open' && $to === 'allocated' && (! $job->customer_id || ! $job->vehicle_id)) {
            throw new InvalidArgumentException('Assign a customer and vehicle before allocating the job.');
        }

        $updates = ['status' => $to];
        if ($to === 'in_progress' && $job->started_at === null) {
            $updates['started_at'] = now();
        }
        if ($to === 'completed') {
            $updates['completed_at'] = now();
        }

        $job->update($updates);

        return $job;
    }

    public function assignTechnician(JobCard $job, int $technicianId): JobCard
    {
        $job->update(['technician_id' => $technicianId]);
        if ($job->status === 'open' && $job->customer_id && $job->vehicle_id) {
            $this->transition($job, 'allocated');
        }

        return $job;
    }

    // ── Labour ───────────────────────────────────────────────────────────

    public function addLabour(JobCard $job, array $data): JobCard
    {
        $code = isset($data['labour_code_id']) ? LabourCode::find($data['labour_code_id']) : null;
        $hours = (float) ($data['hours'] ?? $code?->standard_hours ?? 0);
        $rate = (float) ($data['rate'] ?? ($code ? $code->resolveRate($job->vehicle?->make_id, $job->vehicle?->model_id) : 0));

        // Costing uses the assigned technician's internal cost rate.
        $costRate = (float) ($job->technician?->cost_rate ?? 0);

        $job->labours()->create([
            'labour_code_id' => $code?->id,
            'technician_id' => $data['technician_id'] ?? $job->technician_id,
            'description' => $data['description'] ?? $code?->description ?? 'Labour',
            'hours' => $hours,
            'rate' => $rate,
            'line_total' => round($hours * $rate, 2),
            'cost' => round($hours * $costRate, 2),
            'is_warranty' => $data['is_warranty'] ?? false,
        ]);

        return $job->fresh('labours');
    }

    public function removeLabour(JobCard $job, int $labourId): void
    {
        $job->labours()->whereKey($labourId)->delete();
    }

    // ── Parts requisition ────────────────────────────────────────────────

    public function requestPart(JobCard $job, array $data): JobCardPart
    {
        $part = Part::findOrFail($data['part_id']);

        return $job->parts()->create([
            'part_id' => $part->id,
            'description' => $data['description'] ?? $part->description,
            'qty' => (float) $data['qty'],
            'unit_price' => (float) ($data['unit_price'] ?? 0),
            'status' => 'requested',
            'is_warranty' => $data['is_warranty'] ?? false,
        ]);
    }

    /** Issue a requested part to the job: JOB_CARD_OUT + COGS (DR 5100 / CR 1310). */
    public function issuePart(JobCardPart $line, ?int $userId = null): JobCardPart
    {
        if ($line->status !== 'requested') {
            throw new InvalidArgumentException('Only requested parts can be issued.');
        }

        return DB::transaction(function () use ($line, $userId) {
            $job = $line->jobCard;

            $entry = $this->stock->post(
                $line->part_id, $job->branch_id, 'JOB_CARD_OUT',
                (float) $line->qty, null,
                JobCard::class, $job->id, "Issued to {$job->job_number}", $userId,
            );

            $cost = round((float) $line->qty * (float) $entry->unit_cost, 2);

            // Warranty parts are recovered from the supplier, not expensed to us.
            if (! $line->is_warranty && $cost > 0) {
                $journal = $this->gl->post(
                    journalType: 'adjustment',
                    date: now(),
                    description: "Parts issued to {$job->job_number}",
                    lines: [
                        ['account' => '5100', 'debit' => $cost, 'credit' => 0, 'description' => 'Job parts cost'],
                        ['account' => '1310', 'debit' => 0, 'credit' => $cost, 'description' => 'Stock issued to job'],
                    ],
                    reference: $job->job_number,
                    sourceType: JobCardPart::class,
                    sourceId: $line->id,
                    branch: $job->branch_id,
                    userId: $userId,
                    fromSubLedger: true,
                );
            }

            $line->update([
                'status' => 'issued',
                'unit_cost' => (float) $entry->unit_cost,
                'issued_by' => $userId,
                'issued_at' => now(),
            ]);

            return $line->fresh();
        });
    }

    /** Return an unused issued part: RETURN_IN + reverse the cost (DR 1310 / CR 5100). */
    public function returnPart(JobCardPart $line, ?int $userId = null): JobCardPart
    {
        if ($line->status !== 'issued') {
            throw new InvalidArgumentException('Only issued parts can be returned.');
        }

        return DB::transaction(function () use ($line, $userId) {
            $job = $line->jobCard;

            $this->stock->post(
                $line->part_id, $job->branch_id, 'RETURN_IN',
                (float) $line->qty, (float) $line->unit_cost,
                JobCard::class, $job->id, "Returned from {$job->job_number}", $userId,
            );

            $cost = round((float) $line->qty * (float) $line->unit_cost, 2);
            if (! $line->is_warranty && $cost > 0) {
                $this->gl->post(
                    journalType: 'adjustment',
                    date: now(),
                    description: "Parts returned from {$job->job_number}",
                    lines: [
                        ['account' => '1310', 'debit' => $cost, 'credit' => 0, 'description' => 'Stock returned from job'],
                        ['account' => '5100', 'debit' => 0, 'credit' => $cost, 'description' => 'Job parts cost reversal'],
                    ],
                    reference: $job->job_number,
                    sourceType: JobCardPart::class,
                    sourceId: $line->id,
                    branch: $job->branch_id,
                    userId: $userId,
                    fromSubLedger: true,
                );
            }

            $line->update(['status' => 'returned']);

            return $line->fresh();
        });
    }
}
