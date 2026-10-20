<?php

namespace App\Services;

use App\Models\CustomerReceipt;
use App\Models\GlJournal;
use App\Models\GoodsReceivedNote;
use App\Models\JobCardPart;
use App\Models\SalesDocument;
use App\Models\StockAdjustment;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\SupplierReturn;
use Illuminate\Support\Collection;

/**
 * Turns a journal's polymorphic `source_type` / `source_id` into the
 * `{type, id, label}` triple the shared `<EntityLink>` understands (UI-13:
 * every rendered entity reference is a hyperlink).
 *
 * Mapping (stored FQCN → EntityLink type):
 *   SalesDocument      → invoice | credit_note | quote | order (by document_type)
 *   CustomerReceipt    → receipt
 *   SupplierPayment    → payment
 *   GoodsReceivedNote  → grn
 *   SupplierInvoice    → supplier_invoice
 *   SupplierReturn     → supplier_return
 *   StockAdjustment    → adjustment
 *   JobCardPart        → job (resolves to the owning job card)
 *
 * Journals with no source (manual, opening balances) resolve to null.
 */
class JournalSourceResolver
{
    private const DOC_TYPES = [
        'invoice' => 'invoice',
        'credit_note' => 'credit_note',
        'quotation' => 'quote',
        'order' => 'order',
    ];

    /** @return array{type:string,id:int,label:string}|null */
    public function resolve(?string $sourceType, ?int $sourceId): ?array
    {
        if (! $sourceType || ! $sourceId) {
            return null;
        }

        return $this->resolveMany(collect([[$sourceType, $sourceId]]))["{$sourceType}:{$sourceId}"] ?? null;
    }

    /**
     * Batch-resolve for list pages: one query per source type.
     *
     * @param  Collection<int, array{0:?string,1:?int}|GlJournal>  $pairs
     * @return array<string, array{type:string,id:int,label:string}> keyed "FQCN:id"
     */
    public function resolveMany(Collection $pairs): array
    {
        $grouped = $pairs
            ->map(fn ($p) => $p instanceof GlJournal ? [$p->source_type, $p->source_id] : $p)
            ->filter(fn ($p) => $p[0] && $p[1])
            ->groupBy(fn ($p) => $p[0])
            ->map(fn ($g) => $g->pluck(1)->unique()->values()->all());

        $out = [];
        foreach ($grouped as $class => $ids) {
            foreach ($this->load($class, $ids) as $id => $link) {
                $out["{$class}:{$id}"] = $link;
            }
        }

        return $out;
    }

    /** @return array<int, array{type:string,id:int,label:string}> keyed by source id */
    private function load(string $class, array $ids): array
    {
        return match ($class) {
            SalesDocument::class => SalesDocument::whereIn('id', $ids)->get(['id', 'document_number', 'document_type'])
                ->mapWithKeys(fn ($d) => [$d->id => [
                    'type' => self::DOC_TYPES[$d->document_type] ?? 'invoice',
                    'id' => $d->id,
                    'label' => $d->document_number,
                ]])->all(),
            CustomerReceipt::class => $this->simple(CustomerReceipt::whereIn('id', $ids)->get(['id', 'receipt_number']), 'receipt', 'receipt_number'),
            SupplierPayment::class => $this->simple(SupplierPayment::whereIn('id', $ids)->get(['id', 'payment_number']), 'payment', 'payment_number'),
            GoodsReceivedNote::class => $this->simple(GoodsReceivedNote::whereIn('id', $ids)->get(['id', 'grn_number']), 'grn', 'grn_number'),
            SupplierInvoice::class => $this->simple(SupplierInvoice::whereIn('id', $ids)->get(['id', 'invoice_number']), 'supplier_invoice', 'invoice_number'),
            SupplierReturn::class => $this->simple(SupplierReturn::whereIn('id', $ids)->get(['id', 'return_number']), 'supplier_return', 'return_number'),
            StockAdjustment::class => $this->simple(StockAdjustment::whereIn('id', $ids)->get(['id', 'adjustment_number']), 'adjustment', 'adjustment_number'),
            JobCardPart::class => JobCardPart::with('jobCard:id,job_number')->whereIn('id', $ids)->get(['id', 'job_card_id'])
                ->filter(fn ($p) => $p->jobCard)
                ->mapWithKeys(fn ($p) => [$p->id => [
                    'type' => 'job',
                    'id' => $p->job_card_id,
                    'label' => $p->jobCard->job_number,
                ]])->all(),
            default => [],
        };
    }

    private function simple(Collection $rows, string $type, string $labelField): array
    {
        return $rows->mapWithKeys(fn ($r) => [$r->id => ['type' => $type, 'id' => $r->id, 'label' => $r->{$labelField}]])->all();
    }
}
