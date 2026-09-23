{{-- 80mm thermal variant of the identity block — compact, monospace-friendly. --}}
<div style="text-align:center; font-size:12px; line-height:1.4;">
    <div style="font-size:14px; font-weight:bold;">{{ $identity['trading_name'] ?: $identity['name'] }}</div>
    @if($identity['branch_name'])<div>{{ $identity['branch_name'] }}</div>@endif
    <div>{{ $identity['address'] }}@if($identity['address']), @endif{{ $identity['city'] }}</div>
    @if($identity['phone'])<div>Tel: {{ $identity['phone'] }}</div>@endif
    @if($identity['vat_number'])<div>VAT No: {{ $identity['vat_number'] }}</div>@endif
</div>
<div style="border-bottom:1px dashed #000; margin:6px 0;"></div>
