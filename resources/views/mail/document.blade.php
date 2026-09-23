<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0; padding:0; background:#F5F7FA; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F5F7FA; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0"
                       style="background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #E2E8F2;">
                    {{-- Identity header (shared document identity block) --}}
                    <tr>
                        <td style="background:#0F1B35; padding:20px 28px;">
                            <span style="color:#ffffff; font-size:18px; font-weight:bold;">
                                {{ $identity['trading_name'] ?: $identity['name'] }}
                            </span>
                            @if($identity['branch_name'])
                                <span style="color:#8898BB; font-size:12px;"> · {{ $identity['branch_name'] }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 14px; font-size:18px; color:#1A2744;">{{ $heading }}</h1>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#4A5568;">{!! nl2br(e($bodyText)) !!}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px; border-top:1px solid #E2E8F2; font-size:11px; color:#94A3B8; line-height:1.6;">
                            {{ $identity['name'] }}
                            @if($identity['phone']) · {{ $identity['phone'] }}@endif
                            @if($identity['email']) · {{ $identity['email'] }}@endif
                            @if($identity['vat_number'])<br>VAT No: {{ $identity['vat_number'] }}@endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
