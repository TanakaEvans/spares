<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Document verification — {{ $company }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #f1f5f9; color: #1e293b; }
        .wrap { max-width: 460px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(15,23,42,.08); overflow: hidden; }
        .banner { padding: 22px 20px; text-align: center; color: #fff; }
        .ok { background: linear-gradient(135deg, #059669, #10b981); }
        .bad { background: linear-gradient(135deg, #b91c1c, #ef4444); }
        .banner .icon { font-size: 34px; line-height: 1; }
        .banner h1 { margin: 8px 0 2px; font-size: 18px; }
        .banner p { margin: 0; font-size: 13px; opacity: .9; }
        .body { padding: 20px; }
        .company { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin-bottom: 12px; }
        dl { margin: 0; }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .row dt { color: #64748b; }
        .row dd { margin: 0; font-weight: 600; text-align: right; }
        .code { margin-top: 16px; padding: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; text-align: center; }
        .code span { font-family: ui-monospace, monospace; font-size: 16px; letter-spacing: .08em; font-weight: 700; color: #0f172a; }
        .code small { display: block; color: #64748b; font-size: 11px; margin-top: 2px; }
        .foot { padding: 14px 20px 20px; font-size: 11px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            @if($valid)
                <div class="banner ok">
                    <div class="icon">&#10003;</div>
                    <h1>Authentic document</h1>
                    <p>This stamp was issued by {{ $company }}.</p>
                </div>
                <div class="body">
                    <div class="company">{{ $company }}</div>
                    <dl>
                        <div class="row"><dt>Document</dt><dd>{{ strtoupper(str_replace('_', ' ', $data['t'])) }} · {{ $data['n'] }}</dd></div>
                        @foreach($data['f'] as $label => $value)
                            <div class="row"><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                        @endforeach
                        <div class="row"><dt>Issued</dt><dd>{{ \Illuminate\Support\Carbon::createFromTimestamp($data['i'])->format('d M Y H:i') }}</dd></div>
                    </dl>
                    <div class="code">
                        <span>{{ $code }}</span>
                        <small>Verification code — must match the code printed on the document</small>
                    </div>
                </div>
                <div class="foot">If the figures above differ from your copy, the copy has been altered. This page confirms the values as signed at issue.</div>
            @else
                <div class="banner bad">
                    <div class="icon">&#10007;</div>
                    <h1>Could not verify</h1>
                    <p>This code is invalid, incomplete, or was not issued by {{ $company }}.</p>
                </div>
                <div class="foot">Scan the QR code directly from the original document. A retyped or altered link will not verify.</div>
            @endif
        </div>
    </div>
</body>
</html>
