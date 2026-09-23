<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cash-up {{ $cashup['date'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; padding: 24px 28px; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .muted { color: #64748b; }
        .sec { margin-top: 18px; }
        .sec h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 8px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #475569; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .tot td { font-weight: bold; border-top: 2px solid #cbd5e1; border-bottom: none; }
        .grid { width: 100%; margin-top: 10px; }
        .grid td { border: none; padding: 3px 0; }
        .box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; }
        .big { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    @include('print.partials.document-header', ['identity' => $identity])

    <div class="sec">
        <h1>Daily Cash-up</h1>
        <div class="muted">{{ $branchName }} · {{ \Illuminate\Support\Carbon::parse($cashup['date'])->format('l, d M Y') }}</div>
    </div>

    <table class="grid">
        <tr>
            <td width="25%"><div class="box"><div class="muted">Banked (cash+card+eft)</div><div class="big">{{ number_format($cashup['totals']['banked'], 2) }}</div></div></td>
            <td width="2%"></td>
            <td width="25%"><div class="box"><div class="muted">On account (credit)</div><div class="big">{{ number_format($cashup['totals']['credit'], 2) }}</div></div></td>
            <td width="2%"></td>
            <td width="25%"><div class="box"><div class="muted">Cash in drawer</div><div class="big">{{ number_format($cashup['totals']['cash'], 2) }}</div></div></td>
            <td width="2%"></td>
            <td width="19%"><div class="box"><div class="muted">Change given</div><div class="big">{{ number_format($cashup['totals']['change_given'], 2) }}</div></div></td>
        </tr>
    </table>

    <div class="sec">
        <h2>By payment method</h2>
        <table>
            <thead><tr><th>Method</th><th class="num">Sales</th><th class="num">Receipts</th><th class="num">Total</th><th class="num">Txns</th></tr></thead>
            <tbody>
                @foreach($cashup['by_method'] as $m)
                    <tr>
                        <td>{{ ucfirst($m['method']) }}@if($m['method'] === 'account') <span class="muted">(credit)</span>@endif</td>
                        <td class="num">{{ number_format($m['sales'], 2) }}</td>
                        <td class="num">{{ number_format($m['receipts'], 2) }}</td>
                        <td class="num">{{ number_format($m['total'], 2) }}</td>
                        <td class="num">{{ $m['count'] }}</td>
                    </tr>
                @endforeach
                <tr class="tot">
                    <td>Grand total</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num">{{ number_format($cashup['totals']['grand_total'], 2) }}</td>
                    <td class="num">{{ $cashup['totals']['invoice_count'] + $cashup['totals']['receipt_count'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="sec">
        <h2>By cashier</h2>
        <table>
            <thead><tr><th>Cashier</th><th class="num">Cash</th><th class="num">Card</th><th class="num">EFT</th><th class="num">Account</th><th class="num">Total</th></tr></thead>
            <tbody>
                @forelse($cashup['by_cashier'] as $c)
                    <tr>
                        <td>{{ $c['cashier'] }}</td>
                        <td class="num">{{ number_format($c['cash'], 2) }}</td>
                        <td class="num">{{ number_format($c['card'], 2) }}</td>
                        <td class="num">{{ number_format($c['eft'], 2) }}</td>
                        <td class="num">{{ number_format($c['account'], 2) }}</td>
                        <td class="num">{{ number_format($c['total'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No takings for this day.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sec muted" style="margin-top:28px;">
        Counted by _____________________________  Signature _____________________________  Date __________
    </div>
</body>
</html>
