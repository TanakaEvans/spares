{{-- Compact verification stamp for 80mm receipts. $stamp from DocumentStampService. --}}
@if(!empty($stamp))
    <div class="center" style="margin-top:6px;">
        <img src="{{ $stamp['qr'] }}" alt="Verify QR" style="width:96px; height:96px;"><br>
        <span style="font-size:8px;">Scan to verify</span><br>
        <span style="font-size:9px; font-weight:bold; letter-spacing:1px;">{{ $stamp['code'] }}</span>
    </div>
@endif
