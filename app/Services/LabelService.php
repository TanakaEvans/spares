<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\GoodsReceivedNote;
use App\Models\Part;
use App\Models\StockLevel;
use Illuminate\Support\Collection;

/**
 * Barcode shelf/stock labels (Inventory › Print Labels).
 *
 * What a label encodes: the part's own EAN-13 when it has a valid one,
 * otherwise its stored Code 128 barcode, otherwise the part number as
 * Code 128. Every one of those is matched by the till's exact scan
 * (part number OR either barcode), so a printed label always rings up.
 *
 * How many: a GRN prints one per unit received; a part or a stock run prints
 * one per unit on hand — so every item on the shelf can carry its own label.
 * Sizes, formats and whether the price shows are settings (`labels.*`).
 */
class LabelService
{
    /** Narrowest bar (mm) we trust to scan; also never fewer than 2 printer dots. */
    public const MIN_MODULE_MM = 0.19;

    /** A barcode's quiet zone may run into the label padding, but stays this far off the edge. */
    private const EDGE_MM = 0.5;

    public function __construct(
        private readonly BarcodeService $barcodes,
        private readonly PricingService $pricing,
        private readonly SettingsService $settings,
        private readonly CurrencyService $currency,
    ) {}

    /** The value + symbology a part's label encodes. */
    public function encodingFor(Part $part): array
    {
        $raw = trim((string) $part->barcode_ean) ?: trim((string) $part->barcode_code128) ?: $part->part_number;

        return $this->barcodes->choose($raw);
    }

    /**
     * One label line for the screen: what prints, the price, stock, a preview
     * barcode, and the module count the screen uses to warn about density.
     */
    public function line(Part $part, Branch $branch, ?float $qty = null, ?StockLevel $level = null): array
    {
        $level ??= StockLevel::with('binLocation:id,code')->where('part_id', $part->id)->where('branch_id', $branch->id)->first();
        $onHand = (float) ($level->qty_on_hand ?? 0);
        $enc = $this->encodingFor($part);
        $modules = strlen($this->barcodes->modules($enc['value'], $enc['symbology'])) + 2 * BarcodeService::QUIET;
        $price = $this->priceIncl($part, $branch);
        $showPrice = (bool) $this->settings->get('labels.show_price', $branch->id);
        $fits = [];
        foreach ($this->formats($branch) as $key => $fmt) {
            $fits[$key] = $this->barcodeFit($modules, $fmt, $this->geometry($fmt, $showPrice))['ok'];
        }

        return [
            'part_id' => $part->id,
            'part_number' => $part->part_number,
            'description' => $part->description,
            'bin' => $level?->binLocation?->code,
            'on_hand' => $onHand,
            'price_incl' => $price['incl'],
            'price_source' => $price['source'],
            'value' => $enc['value'],
            'symbology' => $enc['symbology'],
            'modules' => $modules,
            'fits' => $fits,
            'preview' => $this->dataUri($this->barcodes->svg($enc['value'], $enc['symbology'], 60, 14)),
            'qty' => (int) max(1, $qty ?? ceil(max($onHand, 0))),
        ];
    }

    /** Lines for the given parts (default qty = units on hand, at least 1). */
    public function forParts(Collection $parts, Branch $branch, ?callable $qtyFor = null): array
    {
        $levels = StockLevel::with('binLocation:id,code')->where('branch_id', $branch->id)
            ->whereIn('part_id', $parts->pluck('id'))->get()->keyBy('part_id');

        return $parts->map(fn (Part $p) => $this->line($p, $branch, $qtyFor ? $qtyFor($p) : null, $levels->get($p->id)))->values()->all();
    }

    /** A goods received note: one label per unit accepted into stock. */
    public function forGrn(GoodsReceivedNote $grn, Branch $branch): array
    {
        $grn->loadMissing('lines.part');
        $lines = $grn->lines->filter(fn ($l) => $l->part && (float) $l->qty_received > 0);
        $qty = $lines->groupBy('part_id')->map(fn ($g) => (int) ceil($g->sum(fn ($l) => (float) $l->qty_received)));

        return $this->forParts($lines->pluck('part')->unique('id'), $branch, fn (Part $p) => $qty[$p->id] ?? 1);
    }

    /** Every part with stock on hand at the branch (optionally one category). */
    public function stocked(Branch $branch, ?int $categoryId = null, int $limit = 2000): array
    {
        $parts = Part::query()->active()
            ->whereIn('id', StockLevel::where('branch_id', $branch->id)->where('qty_on_hand', '>', 0)->select('part_id'))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderBy('part_number')->limit($limit)->get();

        return $this->forParts($parts, $branch);
    }

    /** Selling price incl. VAT, exactly as the till would charge one unit. */
    public function priceIncl(Part $part, Branch $branch): array
    {
        $resolved = $this->pricing->resolve($part, null, $branch->id);
        if ($resolved['price'] === null) {
            return ['incl' => null, 'source' => 'none'];
        }

        return ['incl' => $this->pricing->computeLine(1, $resolved['price'], 0)['line_total_incl'], 'source' => $resolved['source']];
    }

    // ── Formats & geometry ───────────────────────────────────────────────

    /** Every format with its page/label geometry in mm, for the screen and the PDF. */
    public function formats(Branch $branch): array
    {
        $s = fn (string $k) => $this->settings->get($k, $branch->id);
        $w = (float) $s('labels.thermal_width_mm');
        $h = (float) $s('labels.thermal_height_mm');
        $cols = max(1, (int) $s('labels.a4_columns'));
        $rows = max(1, (int) $s('labels.a4_rows'));
        $m = (float) $s('labels.a4_margin_mm');
        $dpi = (int) $s('labels.printer_dpi');

        return [
            'a4' => [
                'label' => "A4 sheet ({$cols} × {$rows})", 'page_w' => 210.0, 'page_h' => 297.0,
                'cell_w' => round((210 - 2 * $m) / $cols, 3), 'cell_h' => round((297 - 2 * $m) / $rows, 3),
                'margin' => $m, 'cols' => $cols, 'rows' => $rows, 'pad' => 2.0, 'dpi' => 300,
            ],
            'thermal' => [
                'label' => "Label printer ({$w} × {$h} mm)", 'page_w' => $w, 'page_h' => $h,
                'cell_w' => $w, 'cell_h' => $h, 'margin' => 0.0, 'cols' => 1, 'rows' => 1, 'pad' => 1.5, 'dpi' => $dpi,
            ],
            'roll80' => [
                'label' => '80mm receipt roll', 'page_w' => 80.0, 'page_h' => 34.0,
                'cell_w' => 72.0, 'cell_h' => 30.0, 'margin' => 4.0, 'margin_top' => 2.0, 'cols' => 1, 'rows' => 1, 'pad' => 1.0, 'dpi' => $dpi,
            ],
        ];
    }

    /**
     * Size a barcode for a label: bars are a whole number of printer dots wide
     * (crisp at 203/300 dpi) and the quiet zone may use the label padding.
     * `ok` is false when even 2 dots won't fit — the code is too long for this
     * label size and needs a wider format.
     *
     * @return array{x: float, width: float, left: float, ok: bool}
     */
    public function barcodeFit(int $modules, array $fmt, array $geo): array
    {
        $dot = 25.4 / max((int) $fmt['dpi'], 72);
        $avail = $geo['iw'] + 2 * max($fmt['pad'] - self::EDGE_MM, 0);
        $minDots = max(2, (int) ceil(self::MIN_MODULE_MM / $dot - 1e-9));
        $dots = (int) floor($avail / max($modules, 1) / $dot + 1e-9);
        $x = max($dots, 1) * $dot;
        $width = $x * $modules;

        return [
            'x' => round($x, 4),
            'width' => round($width, 3),
            'left' => round(($geo['iw'] - $width) / 2, 3),
            'ok' => $dots >= $minDots,
        ];
    }

    /**
     * Where each text block and the barcode sit inside one label (mm), sized
     * from the label itself so the same layout scales from 50×30 to an A4 cell.
     */
    public function geometry(array $fmt, bool $showPrice): array
    {
        $iw = $fmt['cell_w'] - 2 * $fmt['pad'];
        $ih = $fmt['cell_h'] - 2 * $fmt['pad'];
        $clamp = fn ($v, $lo, $hi) => max($lo, min($hi, $v));

        $pn = $clamp($ih * 0.15, 2.4, 6.0);
        $desc = $clamp($ih * 0.095, 1.8, 3.4);
        $hr = $clamp($ih * 0.075, 1.6, 2.8);
        $foot = $showPrice ? $clamp($ih * 0.14, 2.2, 5.5) : $desc;

        $pnLine = $pn * 1.15;
        $descLine = $desc * 1.25;
        $hrLine = $hr * 1.25;
        $footLine = $foot * 1.15;
        $gap = 0.6;
        $bar = $ih - $pnLine - $descLine - $hrLine - $footLine - 3 * $gap;
        if ($bar < $ih * 0.28) { // small label: drop the description line, keep the barcode scannable
            $descLine = 0;
            $bar = $ih - $pnLine - $hrLine - $footLine - 2 * $gap;
        }

        return [
            'iw' => round($iw, 3), 'ih' => round($ih, 3),
            'pn' => round($pn, 3), 'desc' => round($desc, 3), 'hr' => round($hr, 3), 'foot' => round($foot, 3),
            'desc_top' => round($pnLine, 3),
            'show_desc' => $descLine > 0,
            'bar_top' => round($pnLine + ($descLine > 0 ? $descLine + $gap : $gap), 3),
            'bar_h' => round(max($bar, 4), 3),
            'hr_top' => round($pnLine + ($descLine > 0 ? $descLine + $gap : $gap) + max($bar, 4), 3),
            'foot_top' => round($ih - $footLine, 3),
        ];
    }

    /**
     * Expand [part_id => qty] into the printable labels for one batch, placing
     * each on its page/slot. A4 can start part-way down a used sheet.
     *
     * @param  array<int,int>  $items
     * @return array{pages: array, total: int, batches: int}
     */
    public function layout(array $items, Branch $branch, string $format, int $start, bool $showPrice, int $batch = 1): array
    {
        $fmt = $this->formats($branch)[$format] ?? $this->formats($branch)['a4'];
        $geo = $this->geometry($fmt, $showPrice);
        $max = (int) $this->settings->get('labels.max_per_print');
        $parts = Part::whereIn('id', array_keys($items))->get()->keyBy('id');
        $levels = StockLevel::with('binLocation:id,code')->where('branch_id', $branch->id)
            ->whereIn('part_id', array_keys($items))->get()->keyBy('part_id');
        $symbol = $this->currency->base()->symbol ?? '';
        $decimals = (int) ($this->currency->base()->decimal_places ?? 2);

        // One rendered label per part; repeated qty times.
        $sequence = [];
        foreach ($items as $partId => $qty) {
            $part = $parts->get($partId);
            if (! $part || $qty < 1) {
                continue;
            }
            $enc = $this->encodingFor($part);
            $fit = $this->barcodeFit(strlen($this->barcodes->modules($enc['value'], $enc['symbology'])) + 2 * BarcodeService::QUIET, $fmt, $geo);
            $price = $showPrice ? $this->priceIncl($part, $branch)['incl'] : null;
            // Long part numbers shrink to fit (down to 1.8 mm) before anything is cut off.
            $pnSize = max(1.8, min($geo['pn'], $geo['iw'] / max(mb_strlen($part->part_number) * 0.62, 1)));
            // Descriptions likewise shrink (to 1.8 mm) to show the full vehicle text, then truncate with "…".
            $desc = trim(preg_replace('/\s+/', ' ', (string) $part->description) ?? '');
            $descSize = max(min(1.8, $geo['desc']), min($geo['desc'], $geo['iw'] / max(mb_strlen($desc) * 0.66, 1)));
            $label = [
                'part_number' => $this->fit($part->part_number, $geo['iw'], $pnSize, 0.62),
                'pn_size' => round($pnSize, 3),
                'description' => $this->fit((string) $part->description, $geo['iw'], $descSize, 0.66),
                'desc_size' => round($descSize, 3),
                'bin' => $levels->get($partId)?->binLocation?->code,
                'price' => $price !== null ? $symbol.number_format($price, $decimals) : null,
                'value' => $enc['value'],
                'barcode' => $this->dataUri($this->barcodes->svg($enc['value'], $enc['symbology'], $fit['width'], $geo['bar_h'])),
                'bar_left' => $fit['left'],
                'bar_w' => $fit['width'],
            ];
            for ($i = 0; $i < $qty; $i++) {
                $sequence[] = $label;
            }
        }

        $total = count($sequence);
        $batches = max(1, (int) ceil($total / max($max, 1)));
        $sequence = array_slice($sequence, ($batch - 1) * $max, $max);

        $perPage = $fmt['cols'] * $fmt['rows'];
        $offset = $format === 'a4' ? max(0, min($start - 1, $perPage - 1)) : 0;
        $pages = [];
        foreach ($sequence as $i => $label) {
            $slot = $i + ($batch === 1 ? $offset : 0);
            $page = intdiv($slot, $perPage);
            $within = $slot % $perPage;
            $col = $within % $fmt['cols'];
            $row = intdiv($within, $fmt['cols']);
            $pages[$page][] = $label + [
                'left' => round($fmt['margin'] + $col * $fmt['cell_w'] + $fmt['pad'], 3),
                'top' => round(($fmt['margin_top'] ?? $fmt['margin']) + $row * $fmt['cell_h'] + $fmt['pad'], 3),
            ];
        }

        return ['pages' => array_values($pages), 'total' => $total, 'batches' => $batches, 'format' => $fmt, 'geo' => $geo];
    }

    /** Truncate text to what fits on one line at this font size (DejaVu average glyph widths). */
    private function fit(string $text, float $widthMm, float $fontMm, float $glyph): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        $max = (int) floor($widthMm / max($fontMm * $glyph, 0.1));

        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, max($max - 1, 1))).'…' : $text;
    }

    private function dataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
