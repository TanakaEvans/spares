@php
    // mm → pt for font sizes (DomPDF lays out in points).
    $pt = fn ($mm) => round($mm * 2.8346, 2);
    $f = $layout['format'];
    $g = $layout['geo'];
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Labels</title>
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #000; }
    /* A hair under the paper height: a box exactly page-tall makes DomPDF push it to a fresh page. */
    .page { position: relative; width: {{ $f['page_w'] }}mm; height: {{ $f['page_h'] - 0.6 }}mm; overflow: hidden; }
    .page.next { page-break-before: always; }
    .label { position: absolute; width: {{ $g['iw'] }}mm; height: {{ $g['ih'] }}mm; } /* no clipping: the barcode quiet zone may use the padding */
    .abs { position: absolute; left: 0; width: {{ $g['iw'] }}mm; white-space: nowrap; overflow: hidden; }
    .pn { font-weight: bold; line-height: 1.1; }
    .desc { line-height: 1.2; }
    .bc { position: absolute; left: 0; }
    .hr { text-align: center; font-family: 'DejaVu Sans Mono', monospace; line-height: 1.2; }
    .bin { position: absolute; left: 0; line-height: 1.15; }
    .price { position: absolute; right: 0; font-weight: bold; line-height: 1.15; text-align: right; }
</style>
</head>
<body>
@foreach ($layout['pages'] as $p => $labels)
    <div class="page {{ $loop->first ? '' : 'next' }}">
        @foreach ($labels as $l)
            <div class="label" style="left: {{ $l['left'] }}mm; top: {{ $l['top'] }}mm;">
                <div class="abs pn" style="top: 0; font-size: {{ $pt($l['pn_size']) }}pt;">{{ $l['part_number'] }}</div>
                @if ($g['show_desc'])
                    <div class="abs desc" style="top: {{ $g['desc_top'] }}mm; font-size: {{ $pt($l['desc_size']) }}pt;">{{ $l['description'] }}</div>
                @endif
                <img class="bc" src="{{ $l['barcode'] }}" style="left: {{ $l['bar_left'] }}mm; top: {{ $g['bar_top'] }}mm; width: {{ $l['bar_w'] }}mm; height: {{ $g['bar_h'] }}mm;" alt="">
                <div class="abs hr" style="top: {{ $g['hr_top'] }}mm; font-size: {{ $pt($g['hr']) }}pt;">{{ $l['value'] }}</div>
                @if ($l['bin'])
                    <div class="bin" style="top: {{ $g['foot_top'] }}mm; font-size: {{ $pt($g['desc']) }}pt;">{{ $l['bin'] }}</div>
                @endif
                @if ($l['price'])
                    <div class="price" style="top: {{ $g['foot_top'] }}mm; font-size: {{ $pt($g['foot']) }}pt;">{{ $l['price'] }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endforeach
</body>
</html>
