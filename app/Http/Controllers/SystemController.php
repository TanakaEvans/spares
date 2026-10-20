<?php

namespace App\Http\Controllers;

use App\Models\CustomerReceipt;
use App\Models\GlJournal;
use App\Models\GoodsReceivedNote;
use App\Models\JobCardPart;
use App\Models\SalesDocument;
use App\Models\StockAdjustment;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\SupplierReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    /**
     * Activity log — the audited trail of posted financial events. Every
     * journal (sale, purchase, receipt, payment, adjustment, manual, opening)
     * records who posted it and when, with a link back to the source.
     */
    public function logs(Request $request): Response
    {
        $journals = GlJournal::query()
            ->with('branch:id,name')
            ->leftJoin('auth_users', 'auth_users.id', '=', 'gl_journals.posted_by')
            ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('journal_type', $t))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                fn ($x) => $x->where('journal_number', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
            ))
            ->orderByDesc('gl_journals.posted_at')->orderByDesc('gl_journals.id')
            ->select('gl_journals.*', 'auth_users.name as posted_by_name')
            ->paginate(30)
            ->withQueryString();

        $sources = $this->sourceLinks($journals->getCollection());
        $journals->through(fn (GlJournal $j) => [
            'id' => $j->id,
            'journal_number' => $j->journal_number,
            'type' => $j->journal_type,
            'description' => $j->description,
            'reference' => $j->reference,
            'branch' => $j->branch?->name,
            'branch_id' => $j->branch_id,
            'user' => $j->posted_by_name ?? 'System',
            'user_id' => $j->posted_by,
            'source' => $sources[$j->id] ?? null,
            'at' => $j->posted_at?->toDateTimeString(),
        ]);

        return Inertia::render('System/Logs', [
            'journals' => $journals,
            'filters' => $request->only('search', 'type'),
            'types' => ['sales', 'purchase', 'receipt', 'payment', 'adjustment', 'manual', 'opening'],
        ]);
    }

    /**
     * Resolve each journal's source document to an EntityLink descriptor
     * ({type, id, label}) with one query per source class, not per row.
     *
     * @return array<int, array{type:string,id:int,label:string}>
     */
    private function sourceLinks(Collection $journals): array
    {
        $byClass = $journals->filter(fn ($j) => $j->source_type && $j->source_id)->groupBy('source_type');
        $out = [];

        foreach ($byClass as $class => $items) {
            $ids = $items->pluck('source_id')->unique()->all();
            $labels = match ($class) {
                SalesDocument::class => SalesDocument::whereIn('id', $ids)->get(['id', 'document_type', 'document_number'])
                    ->mapWithKeys(fn ($d) => [$d->id => ['type' => match ($d->document_type) {
                        'credit_note' => 'credit_note', 'quotation' => 'quote', 'order' => 'order', default => 'invoice'
                    }, 'label' => $d->document_number]]),
                CustomerReceipt::class => CustomerReceipt::whereIn('id', $ids)->pluck('receipt_number', 'id')->map(fn ($n) => ['type' => 'receipt', 'label' => $n]),
                SupplierPayment::class => SupplierPayment::whereIn('id', $ids)->pluck('payment_number', 'id')->map(fn ($n) => ['type' => 'payment', 'label' => $n]),
                GoodsReceivedNote::class => GoodsReceivedNote::whereIn('id', $ids)->pluck('grn_number', 'id')->map(fn ($n) => ['type' => 'grn', 'label' => $n]),
                SupplierInvoice::class => SupplierInvoice::whereIn('id', $ids)->pluck('invoice_number', 'id')->map(fn ($n) => ['type' => 'supplier_invoice', 'label' => $n]),
                SupplierReturn::class => SupplierReturn::whereIn('id', $ids)->pluck('return_number', 'id')->map(fn ($n) => ['type' => 'supplier_return', 'label' => $n]),
                StockAdjustment::class => StockAdjustment::whereIn('id', $ids)->pluck('adjustment_number', 'id')->map(fn ($n) => ['type' => 'adjustment', 'label' => $n]),
                JobCardPart::class => JobCardPart::with('jobCard:id,job_number')->whereIn('id', $ids)->get()
                    ->mapWithKeys(fn ($p) => [$p->id => ['type' => 'job', 'id' => $p->job_card_id, 'label' => $p->jobCard?->job_number ?? "Job #{$p->job_card_id}"]]),
                default => collect(),
            };

            foreach ($items as $j) {
                if ($meta = $labels[$j->source_id] ?? null) {
                    $out[$j->id] = ['type' => $meta['type'], 'id' => $meta['id'] ?? $j->source_id, 'label' => $meta['label']];
                }
            }
        }

        return $out;
    }
}
