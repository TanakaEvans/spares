<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->document_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans Mono, monospace; font-size: 10px; color: #000; padding: 6px 8px; }
        .line { width: 100%; }
        .line td { padding: 1px 0; vertical-align: top; }
        .num { text-align: right; }
        .rule { border-bottom: 1px dashed #000; margin: 5px 0; }
        .tot { font-weight: bold; font-size: 12px; }
        .center { text-align: center; }
    </style>
</head>
<body>
    @include('print.partials.document-header-thermal', ['identity' => $identity])

    <table class="line">
        <tr><td>{{ $invoice->document_number }}</td><td class="num">{{ $invoice->document_date->format('d/m/Y') }}</td></tr>
        <tr><td colspan="2">Customer: {{ $invoice->customer?->name ?? 'Cash' }}</td></tr>
    </table>
    <div class="rule"></div>

    <table class="line">
        @foreach($invoice->lines as $line)
            <tr>
                <td colspan="2">{{ $line->part?->part_number ?? '' }} {{ \Illuminate\Support\Str::limit($line->description, 28) }}</td>
            </tr>
            <tr>
                <td>{{ number_format((float) $line->qty, 2) }} x {{ number_format((float) $line->unit_price, 2) }}@if((float) $line->discount_pct > 0) (-{{ number_format((float) $line->discount_pct, 0) }}%)@endif</td>
                <td class="num">{{ number_format((float) $line->line_total_incl, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="rule"></div>

    <table class="line">
        <tr><td>Subtotal</td><td class="num">{{ number_format((float) $invoice->subtotal_excl, 2) }}</td></tr>
        <tr><td>VAT</td><td class="num">{{ number_format((float) $invoice->vat_amount, 2) }}</td></tr>
        <tr class="tot"><td>TOTAL</td><td class="num">{{ number_format((float) $invoice->total_incl, 2) }}</td></tr>
    </table>
    <div class="rule"></div>

    <table class="line">
        @foreach($invoice->payments as $p)
            <tr><td>{{ ucfirst($p->method) }}</td><td class="num">{{ number_format((float) $p->amount, 2) }}</td></tr>
            @if((float) $p->tendered > 0)
                <tr><td>Tendered</td><td class="num">{{ number_format((float) $p->tendered, 2) }}</td></tr>
            @endif
        @endforeach
        @if($invoice->payments->sum('change_given') > 0)
            <tr><td>CHANGE</td><td class="num">{{ number_format((float) $invoice->payments->sum('change_given'), 2) }}</td></tr>
        @endif
    </table>
    <div class="rule"></div>

    <div class="center">
        @if($identity['vat_number'])VAT No: {{ $identity['vat_number'] }}<br>@endif
        {{ $footerText }}<br>
        Thank you!
    </div>
</body>
</html>
