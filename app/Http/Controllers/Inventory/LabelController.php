<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoodsReceivedNote;
use App\Models\Part;
use App\Models\PartCategory;
use App\Models\StockLevel;
use App\Services\DocumentPdfService;
use App\Services\LabelService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Inventory › Print Labels: barcode labels for parts — from a part, a goods
 * received note, every part in stock, or a hand-picked list — on an A4 sheet,
 * a label printer or the 80mm receipt printer. All label logic lives in
 * LabelService; this controller only shapes HTTP.
 */
class LabelController extends Controller
{
    public function index(Request $request, LabelService $labels, SettingsService $settings): Response
    {
        $branch = $this->branch($request);
        [$lines, $source] = $this->initialLines($request, $labels, $branch);

        return Inertia::render('Inventory/Labels/Index', [
            'branch' => $branch->only(['id', 'name']),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'lines' => $lines,
            'source' => $source,
            'formats' => collect($labels->formats($branch))->map(fn ($f) => [
                'label' => $f['label'], 'cell_w' => $f['cell_w'], 'cell_h' => $f['cell_h'],
                'cols' => $f['cols'], 'rows' => $f['rows'], 'per_page' => $f['cols'] * $f['rows'],
            ]),
            'defaults' => [
                'format' => (string) $settings->get('labels.default_format', $branch->id),
                'show_price' => (bool) $settings->get('labels.show_price', $branch->id),
                'max_per_print' => (int) $settings->get('labels.max_per_print'),
            ],
            'categories' => PartCategory::where('is_active', true)
                ->whereIn('id', Part::query()->active()
                    ->whereIn('id', StockLevel::where('branch_id', $branch->id)->where('qty_on_hand', '>', 0)->select('part_id'))
                    ->select('category_id'))
                ->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * JSON for the screen. `q` = picker search (part rows); `ids` or
     * `stocked=1` = full label lines ready to add to the batch.
     */
    public function lookup(Request $request, LabelService $labels): JsonResponse
    {
        $branch = $this->branch($request);

        if ($request->filled('q')) {
            $q = trim((string) $request->query('q'));
            if (mb_strlen($q) < 2) {
                return response()->json(['parts' => []]);
            }
            $parts = Part::query()->active()->search($q)->orderBy('part_number')->limit(15)->get();
            $levels = StockLevel::with('binLocation:id,code')->where('branch_id', $branch->id)
                ->whereIn('part_id', $parts->pluck('id'))->get()->keyBy('part_id');

            return response()->json(['parts' => $parts->map(fn (Part $p) => [
                'id' => $p->id, 'part_number' => $p->part_number, 'description' => $p->description,
                'on_hand' => (float) ($levels->get($p->id)->qty_on_hand ?? 0),
                'bin' => $levels->get($p->id)?->binLocation?->code,
            ])->values()]);
        }

        if ($request->boolean('stocked')) {
            return response()->json(['lines' => $labels->stocked($branch, $request->integer('category_id') ?: null)]);
        }

        $ids = $this->ids((string) $request->query('ids', ''));

        return response()->json(['lines' => $labels->forParts(Part::whereIn('id', $ids)->get(), $branch)]);
    }

    /** The labels as a PDF: items=ID:QTY,ID:QTY … */
    public function print(Request $request, LabelService $labels, DocumentPdfService $pdf, SettingsService $settings): HttpResponse
    {
        $branch = $this->branch($request);
        $formats = $labels->formats($branch);
        $data = $request->validate([
            'items' => ['required', 'string', 'max:20000'],
            'format' => ['nullable', 'in:'.implode(',', array_keys($formats))],
            'start' => ['nullable', 'integer', 'min:1', 'max:200'],
            'price' => ['nullable', 'boolean'],
            'batch' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $items = [];
        foreach (explode(',', $data['items']) as $pair) {
            [$id, $qty] = array_pad(explode(':', trim($pair)), 2, 1);
            if (ctype_digit((string) $id) && (int) $qty > 0) {
                $items[(int) $id] = min(($items[(int) $id] ?? 0) + (int) $qty, 9999);
            }
        }
        abort_if($items === [], 422, 'No labels to print.');

        // A full batch of label-printer pages takes DomPDF ~20 s; leave headroom over the 30 s default.
        @set_time_limit(180);

        $format = $data['format'] ?? (string) $settings->get('labels.default_format', $branch->id);
        $showPrice = array_key_exists('price', $data) ? (bool) $data['price'] : (bool) $settings->get('labels.show_price', $branch->id);
        $layout = $labels->layout($items, $branch, $format, (int) ($data['start'] ?? 1), $showPrice, (int) ($data['batch'] ?? 1));
        abort_if($layout['pages'] === [], 422, 'Nothing to print in that batch.');

        $fmt = $layout['format'];
        $paper = $format === 'a4' ? 'a4' : [0, 0, round($fmt['page_w'] * 2.8346, 2), round($fmt['page_h'] * 2.8346, 2)];
        $suffix = $layout['batches'] > 1 ? '-part'.($data['batch'] ?? 1) : '';

        return $pdf->render('print.labels', ['layout' => $layout], $branch, $paper)
            ->stream('labels-'.now()->format('Ymd-Hi').$suffix.'.pdf');
    }

    /** @return array{0: array, 1: ?array} lines + the document they came from */
    private function initialLines(Request $request, LabelService $labels, Branch $branch): array
    {
        if ($grnId = $request->integer('grn')) {
            $grn = GoodsReceivedNote::findOrFail($grnId);

            return [$labels->forGrn($grn, $branch), ['type' => 'grn', 'id' => $grn->id, 'label' => $grn->grn_number]];
        }
        if ($partId = $request->integer('part')) {
            $part = Part::findOrFail($partId);

            return [$labels->forParts(collect([$part]), $branch), null];
        }
        if ($request->filled('parts')) {
            return [$labels->forParts(Part::whereIn('id', $this->ids((string) $request->query('parts')))->get(), $branch), null];
        }

        return [[], null];
    }

    /** @return list<int> */
    private function ids(string $csv): array
    {
        return array_slice(array_values(array_unique(array_filter(array_map('intval', explode(',', $csv))))), 0, 2000);
    }

    private function branch(Request $request): Branch
    {
        if ($id = $request->integer('branch_id')) {
            return Branch::findOrFail($id);
        }

        return Branch::where('is_main_branch', true)->first() ?? Branch::firstOrFail();
    }
}
