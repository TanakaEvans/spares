<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { size: A4 landscape; margin: 14mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .head { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #cbd5e1; padding-bottom: 6px; margin-bottom: 10px; }
        .company { font-size: 14px; font-weight: bold; }
        h1 { font-size: 13px; }
        .muted { color: #64748b; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px 6px; text-align: left; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        th { background: #f1f5f9; font-size: 8px; text-transform: uppercase; letter-spacing: .03em; color: #475569; }
        tbody tr:nth-child(even) { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="head">
        <div>
            <div class="company">{{ $identity['company_name'] ?? config('app.name') }}</div>
            <h1>{{ $title }}</h1>
        </div>
        <div class="muted">Generated {{ $generatedAt }} · {{ count($rows) }} rows</div>
    </div>

    <table>
        <thead>
            <tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" class="muted">No rows.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
