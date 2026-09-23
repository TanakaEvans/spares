<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->document_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1A2744; padding: 24px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;
                         color: #888; border-bottom: 1px solid #0F1B35; padding: 6px 8px; }
        table.lines td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .totals { width: 260px; margin-left: auto; margin-top: 10px; }
        .totals td { padding: 3px 8px; }
        .totals .grand { font-size: 13px; font-weight: bold; border-top: 2px solid #0F1B35; }
        .block { margin-bottom: 12px; font-size: 10px; color: #444; }
        .block strong { color: #0F1B35; }
        .pay { width: 260px; margin-top: 12px; font-size: 10px; color: #444; }
        .pay td { padding: 2px 8px; }
    </style>
</head>
<body>
    @include('print.partials.document-header', [
        'identity' => $identity,
        'documentTitle' => 'TAX INVOICE',
        'meta' => [
            'Invoice No' => $invoice->document_number,
            'Date' => $invoice->document_date->format('d M Y'),
            'Branch' => $invoice->branch?->name,
        ],
    ])

    <div class="block">
        <strong>Bill to:</strong> {{ $invoice->customer?->name ?? 'Cash Customer' }}
        @if($invoice->customer?->vat_number) · VAT {{ $invoice->customer->vat_number }}@endif
        @if($invoice->customer?->phone) · {{ $invoice->customer->phone }}@endif
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Part No</th>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit (excl)</th>
                <th class="num">Disc</th>
                <th class="num">VAT</th>
                <th class="num">Total (incl)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
                <tr>
                    <td>{{ $line->part?->part_number }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="num">{{ number_format((float) $line->qty, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="num">{{ (float) $line->discount_pct > 0 ? number_format((float) $line->discount_pct, 1).'%' : '—' }}</td>
                    <td class="num">{{ number_format((float) $line->vat_amount, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->line_total_incl, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal (excl VAT)</td><td class="num">{{ number_format((float) $invoice->subtotal_excl, 2) }}</td></tr>
        @if((float) $invoice->discount_amount > 0)
            <tr><td>Discount</td><td class="num">-{{ number_format((float) $invoice->discount_amount, 2) }}</td></tr>
        @endif
        <tr><td>VAT ({{ rtrim(rtrim(number_format((float) $vatRate, 2), '0'), '.') }}%)</td><td class="num">{{ number_format((float) $invoice->vat_amount, 2) }}</td></tr>
        <tr class="grand"><td>TOTAL</td><td class="num">{{ number_format((float) $invoice->total_incl, 2) }}</td></tr>
    </table>

    <table class="pay" style="margin-left:auto;">
        @foreach($invoice->payments as $p)
            <tr>
                <td>{{ ucfirst($p->method) }}@if($p->reference) ({{ $p->reference }})@endif</td>
                <td class="num">{{ number_format((float) $p->amount, 2) }}</td>
            </tr>
        @endforeach
        @if($invoice->payments->sum('change_given') > 0)
            <tr><td>Change</td><td class="num">{{ number_format((float) $invoice->payments->sum('change_given'), 2) }}</td></tr>
        @endif
    </table>

    @include('print.partials.document-footer', [
        'identity' => $identity,
        'footerText' => $footerText,
    ])
</body>
</html>
