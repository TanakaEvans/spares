{{-- The ONE document identity block. Enter once in System Admin, rendered on every
     printed document (invoice, quote, PO, GRN, statement, job card…).
     $identity comes from DocumentIdentityService::for($branch).
     $documentTitle e.g. "TAX INVOICE"; $meta = ['Invoice No' => 'INV-…', 'Date' => …] --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:16px;">
    <tr>
        <td style="vertical-align:top; width:55%;">
            @if(!empty($identity['logo']))
                <img src="{{ public_path('storage/'.$identity['logo']) }}" alt="" style="max-height:60px; margin-bottom:6px;">
            @endif
            <div style="font-size:16px; font-weight:bold; color:#0F1B35;">
                {{ $identity['trading_name'] ?: $identity['name'] }}
            </div>
            @if($identity['branch_name'])
                <div style="font-size:11px; color:#555;">{{ $identity['branch_name'] }}</div>
            @endif
            <div style="font-size:10px; color:#555; line-height:1.5; margin-top:4px;">
                @if($identity['address']){{ $identity['address'] }}, @endif{{ $identity['city'] }}, {{ $identity['country'] }}<br>
                @if($identity['phone'])Tel: {{ $identity['phone'] }} · @endif
                @if($identity['email']){{ $identity['email'] }}@endif
                @if($identity['website'])<br>{{ $identity['website'] }}@endif
            </div>
            <div style="font-size:10px; color:#555; margin-top:4px;">
                @if($identity['registration_number'])Reg No: {{ $identity['registration_number'] }} · @endif
                @if($identity['vat_number'])<strong>VAT No: {{ $identity['vat_number'] }}</strong>@endif
            </div>
        </td>
        <td style="vertical-align:top; width:45%; text-align:right;">
            <div style="font-size:20px; font-weight:bold; color:#E8590C; letter-spacing:1px;">
                {{ $documentTitle }}
            </div>
            <table style="margin-left:auto; margin-top:8px; font-size:10px; color:#333;">
                @foreach(($meta ?? []) as $label => $value)
                    <tr>
                        <td style="padding:1px 8px 1px 0; color:#888; text-align:right;">{{ $label }}:</td>
                        <td style="padding:1px 0; font-weight:bold; text-align:right;">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>
<div style="border-bottom:2px solid #0F1B35; margin-bottom:14px;"></div>
