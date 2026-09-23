<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $creditNote->document_number }}</title>
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
    </style>
</head>
<body>
    @include('print.partials.document-header', [
        'identity' => $identity,
        'documentTitle' => 'CREDIT NOTE',
        'meta' => [
            'Credit No' => $creditNote->document_number,
            'Date' => $creditNote->document_date->format('d M Y'),
            'Against' => $creditNote->parent?->document_number ?? '—',
        ],
    ])

    <div class="block">
        <strong>Customer:</strong> {{ $creditNote->customer?->name ?? 'Cash Customer' }}<br>
        <strong>Reason:</strong> {{ $creditNote->reason }} ·
        <strong>Refund:</strong> {{ $creditNote->credit_mode === 'refund_cash' ? 'Cash' : 'To account' }}
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Part No</th>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit (excl)</th>
                <th class="num">Total (incl)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($creditNote->lines as $line)
                <tr>
                    <td>{{ $line->part?->part_number }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="num">{{ number_format((float) $line->qty, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->line_total_incl, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal (excl VAT)</td><td class="num">{{ number_format((float) $creditNote->subtotal_excl, 2) }}</td></tr>
        <tr><td>VAT</td><td class="num">{{ number_format((float) $creditNote->vat_amount, 2) }}</td></tr>
        <tr class="grand"><td>TOTAL CREDITED</td><td class="num">{{ number_format((float) $creditNote->total_incl, 2) }}</td></tr>
    </table>

    @include('print.partials.document-footer', [
        'identity' => $identity,
        'footerText' => $footerText,
    ])
</body>
</html>
