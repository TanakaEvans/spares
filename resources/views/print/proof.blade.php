<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Document Proof</title>
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
    </style>
</head>
<body>
    @include('print.partials.document-header', [
        'identity' => $identity,
        'documentTitle' => 'DOCUMENT PROOF',
        'meta' => [
            'Proof No' => $proofNumber,
            'Date' => $date,
            'Printed by' => $printedBy,
        ],
    ])

    <p style="font-size:10px; color:#555; margin-bottom:10px;">
        This proof confirms the shared document pipeline: identity block, sample line table,
        totals, banking details and configurable footer — the same skeleton every invoice,
        quotation, purchase order and statement will use.
    </p>

    <table class="lines">
        <thead>
            <tr>
                <th>Part No</th>
                <th>Description</th>
                <th class="num">Qty</th>
                <th class="num">Unit Price</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>90915-YZZD3</td>
                <td>Oil Filter — Toyota OEM (Hilux 1GD-FTV)</td>
                <td class="num">4</td>
                <td class="num">6.50</td>
                <td class="num">26.00</td>
            </tr>
            <tr>
                <td>BKR6E-11</td>
                <td>NGK Spark Plug</td>
                <td class="num">4</td>
                <td class="num">12.00</td>
                <td class="num">48.00</td>
            </tr>
            <tr>
                <td>MAG-5W30-1L</td>
                <td>Castrol Magnatec 5W-30 1L</td>
                <td class="num">1</td>
                <td class="num">8.50</td>
                <td class="num">8.50</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal (excl VAT)</td><td class="num">82.50</td></tr>
        <tr><td>VAT @ {{ $vatRate }}%</td><td class="num">{{ number_format(82.50 * $vatRate / 100, 2) }}</td></tr>
        <tr class="grand"><td>TOTAL</td><td class="num">{{ number_format(82.50 * (1 + $vatRate / 100), 2) }}</td></tr>
    </table>

    @include('print.partials.document-footer', [
        'identity' => $identity,
        'footerText' => $footerText,
    ])
</body>
</html>
