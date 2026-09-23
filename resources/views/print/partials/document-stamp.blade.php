{{-- Digital verification stamp: HMAC-signed QR + human code. $stamp from DocumentStampService. --}}
@if(!empty($stamp))
    <div style="margin-top:22px; border-top:1px dashed #cbd5e1; padding-top:12px;">
        <table style="width:100%; table-layout:fixed; border-collapse:collapse;">
            <tr>
                <td style="width:120px; vertical-align:middle; text-align:center;">
                    {{-- Circular seal --}}
                    <div style="width:100px; height:100px; border:3px double #0F1B35; border-radius:50px; padding:11px 6px; margin:0 auto; text-align:center; color:#0F1B35;">
                        <div style="font-size:6px; letter-spacing:1px; text-transform:uppercase;">Digitally Verified</div>
                        <div style="font-size:12px; font-weight:bold; margin:3px 0 1px;">{{ strtoupper(mb_substr($stamp['facts']['Party'] ?? 'D', 0, 1)) }}{{ strtoupper(substr(str_replace(['-','_',' '],'',$stamp['type']),0,2)) }}</div>
                        <div style="font-size:6.5px;">{{ $stamp['number'] }}</div>
                        <div style="font-size:5.5px; color:#475569; margin-top:2px;">{{ $stamp['issued_at'] }}</div>
                        <div style="font-size:6.5px; font-family:'DejaVu Sans Mono',monospace; font-weight:bold; margin-top:3px;">{{ $stamp['code'] }}</div>
                    </div>
                </td>
                <td style="vertical-align:middle; padding:0 12px; overflow:hidden;">
                    <div style="font-size:10px; font-weight:bold; color:#0F1B35;">Authenticity stamp</div>
                    <div style="font-size:8.5px; color:#475569; margin:2px 0 4px; line-height:1.4;">
                        This document carries a cryptographic signature. Scan the QR code (or visit the link it opens) and
                        confirm the figures and the verification code shown there match this page — an altered copy will not verify.
                    </div>
                    <div style="font-size:9px; color:#0F1B35;">
                        Verification code:
                        <strong style="font-family:'DejaVu Sans Mono',monospace; letter-spacing:1px;">{{ $stamp['code'] }}</strong>
                    </div>
                    {{-- Microprint (wraps inside this cell; blurs when photocopied) --}}
                    <div style="font-size:3px; line-height:4px; color:#94a3b8; margin-top:5px; max-height:8px; overflow:hidden; word-break:break-all;">{{ str_repeat($stamp['number'].'·'.$stamp['code'].'·', 10) }}</div>
                </td>
                <td style="width:100px; vertical-align:middle; text-align:center;">
                    <img src="{{ $stamp['qr'] }}" alt="Verify QR" style="width:90px; height:90px;">
                    <div style="font-size:6px; color:#475569;">Scan to verify</div>
                </td>
            </tr>
        </table>
    </div>
@endif
