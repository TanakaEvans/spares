<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCard extends Model
{
    protected $fillable = [
        'job_number', 'customer_id', 'vehicle_id', 'branch_id', 'technician_id', 'status',
        'reported_fault', 'work_done', 'odometer_in', 'odometer_out', 'authorisation_required',
        'promised_at', 'sales_document_id', 'opened_by', 'started_at', 'completed_at', 'invoiced_at', 'notes',
    ];

    protected $casts = [
        'authorisation_required' => 'boolean',
        'promised_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'invoiced_at' => 'datetime',
    ];

    public const FLOW = [
        'open' => ['allocated', 'cancelled'],
        'allocated' => ['in_progress', 'cancelled'],
        'in_progress' => ['awaiting_parts', 'awaiting_customer', 'on_hold', 'quality_check', 'cancelled'],
        'awaiting_parts' => ['in_progress', 'cancelled'],
        'awaiting_customer' => ['in_progress', 'cancelled'],
        'on_hold' => ['in_progress', 'cancelled'],
        'quality_check' => ['completed', 'in_progress'],
        'completed' => ['invoiced'],
        'invoiced' => ['closed'],
        'closed' => [],
        'cancelled' => [],
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class, 'vehicle_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    public function labours(): HasMany
    {
        return $this->hasMany(JobCardLabour::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(JobCardPart::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class, 'sales_document_id');
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::FLOW[$this->status] ?? [], true);
    }

    // ── Costing (Module 4.6) ─────────────────────────────────────────────

    public function labourBilled(): float
    {
        return round((float) $this->labours->where('is_warranty', false)->sum('line_total'), 2);
    }

    public function partsBilled(): float
    {
        return round((float) $this->parts->where('is_warranty', false)
            ->where('status', '!=', 'returned')
            ->sum(fn (JobCardPart $p) => (float) $p->qty * (float) $p->unit_price), 2);
    }

    public function labourCost(): float
    {
        return round((float) $this->labours->sum('cost'), 2);
    }

    public function partsCost(): float
    {
        return round((float) $this->parts->where('status', 'issued')
            ->sum(fn (JobCardPart $p) => (float) $p->qty * (float) $p->unit_cost), 2);
    }

    public function totalCost(): float
    {
        return round($this->labourCost() + $this->partsCost(), 2);
    }

    public function totalBilled(): float
    {
        return round($this->labourBilled() + $this->partsBilled(), 2);
    }

    public function marginPct(): float
    {
        $billed = $this->totalBilled();

        return $billed > 0 ? round(($billed - $this->totalCost()) / $billed * 100, 1) : 0.0;
    }
}
