{{-- Shared document footer: banking details + configurable footer text.
     $identity from DocumentIdentityService; $footerText from SettingsService. --}}
<div style="margin-top:20px; border-top:1px solid #ccc; padding-top:10px; font-size:9px; color:#666;">
    @if($identity['bank_name'])
        <div style="margin-bottom:6px;">
            <strong>Banking details:</strong>
            {{ $identity['bank_name'] }}
            @if($identity['bank_branch_code']) · Branch {{ $identity['bank_branch_code'] }}@endif
            @if($identity['bank_account_name']) · {{ $identity['bank_account_name'] }}@endif
            @if($identity['bank_account_number']) · Acc {{ $identity['bank_account_number'] }}@endif
        </div>
    @endif
    @if(!empty($footerText))
        <div>{{ $footerText }}</div>
    @endif
</div>
