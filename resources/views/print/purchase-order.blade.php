<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $order->po_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1A2744; padding: 24px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;
                         color: #888; border-bottom: 1px solid #0F1B35; padding: 6px 8px; }
        table.lines td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .totals { width: 240px; margin-left: auto; margin-top: 10px; }
        .totals td { padding: 3px 8px; }
        .totals .grand { font-size: 13px; font-weight: bold; border-top: 2px solid #0F1B35; }
        .block { margin-bottom: 12px; font-size: 10px; color: #444; }
        .block strong { color: #0F1B35; }
    </style>
</head>
<body>
    @include('print.partials.document-header', [
        'identity' => $identity,
        'documentTitle' => 'PURCHASE ORDER',
        'meta' => [
            'PO No' => $order->po_number,
            'Date' => $order->order_date->format('d M Y'),
            'Expected' => $order->expected_date?->format('d M Y') ?? '—',
            'Status' => strtoupper($order->status),
        ],
    ])

    <div class="block">
        <strong>To:</strong> {{ $order->supplier->name }}
        @if($order->supplier->email) · {{ $order->supplier->email }}@endif
        @if($order->supplier->phone) · {{ $order->supplier->phone }}@endif<br>
        <strong>Deliver to:</strong> {{ $order->branch->name }}
        @if($order->supplier_ref)<br><strong>Your ref:</strong> {{ $order->supplier_ref }}@endif
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Part No</th>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit Cost</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->lines as $line)
                <tr>
                    <td>{{ $line->part?->part_number }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="num">{{ number_format((float) $line->qty_ordered, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->unit_cost, 2) }}</td>
                    <td class="num">{{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="grand"><td>ORDER TOTAL</td><td class="num">{{ number_format((float) $order->total, 2) }}</td></tr>
    </table>

    @if($order->notes)
        <div class="block" style="margin-top:12px;"><strong>Notes:</strong> {{ $order->notes }}</div>
    @endif

    @include('print.partials.document-footer', [
        'identity' => $identity,
        'footerText' => 'Please quote the PO number on your delivery note and invoice.',
    ])
</body>
</html>
