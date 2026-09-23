<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Statement — {{ $customer->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1A2744; padding: 24px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;
                         color: #888; border-bottom: 1px solid #0F1B35; padding: 6px 8px; }
        table.lines td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .block { margin-bottom: 12px; font-size: 10px; color: #444; }
        .block strong { color: #0F1B35; }
        .ageing { width: 100%; margin-top: 14px; border-collapse: collapse; }
        .ageing td, .ageing th { border: 1px solid #ccc; padding: 6px 8px; font-size: 10px; text-align: right; }
        .ageing th { background: #f5f5f5; color: #555; text-transform: uppercase; font-size: 9px; }
        .grand { font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>
    @include('print.partials.document-header', [
        'identity' => $identity,
        'documentTitle' => 'STATEMENT',
        'meta' => [
            'Account' => $customer->customer_number,
            'Date' => now()->format('d M Y'),
            'Terms' => $customer->payment_terms_days.' days',
        ],
    ])

    <div class="block">
        <strong>To:</strong> {{ $customer->name }}
        @if($customer->vat_number) · VAT {{ $customer->vat_number }}@endif<br>
        @if($customer->address){{ $customer->address }}, @endif{{ $customer->city }}
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Detail</th>
                <th class="num">Debit</th>
                <th class="num">Credit</th>
                <th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $l)
                <tr>
                    <td>{{ $l['date'] }}</td>
                    <td>{{ $l['ref'] }}</td>
                    <td>{{ $l['detail'] }}</td>
                    <td class="num">{{ $l['debit'] > 0 ? number_format($l['debit'], 2) : '' }}</td>
                    <td class="num">{{ $l['credit'] > 0 ? number_format($l['credit'], 2) : '' }}</td>
                    <td class="num">{{ number_format($l['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; color:#888; padding:16px;">No account activity.</td></tr>
            @endforelse
            <tr class="grand">
                <td colspan="5" class="num">BALANCE DUE</td>
                <td class="num">{{ number_format($closing, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="ageing">
        <thead>
            <tr><th>Current</th><th>30 Days</th><th>60 Days</th><th>90+ Days</th><th>Total</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($ageing['current'], 2) }}</td>
                <td>{{ number_format($ageing['b30'], 2) }}</td>
                <td>{{ number_format($ageing['b60'], 2) }}</td>
                <td>{{ number_format($ageing['b90'], 2) }}</td>
                <td><strong>{{ number_format($ageing['total'], 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    @include('print.partials.document-footer', [
        'identity' => $identity,
        'footerText' => 'Please settle overdue amounts promptly. Queries: contact your account manager.',
    ])
</body>
</html>
