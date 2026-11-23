<?php

namespace App\Services;

/**
 * Self-contained barcode encoder (no external dependency, works offline):
 * Code 128 with automatic B/C subset switching, and EAN-13. Output is a module
 * string ("1" = bar, "0" = space) that renders to a crisp vector SVG sized in
 * millimetres, so labels print sharply on any printer resolution.
 */
class BarcodeService
{
    /** Quiet zone each side, in modules (spec minimum is 10 for both symbologies). */
    public const QUIET = 10;

    /** Code 128 symbol patterns (bar/space widths) for values 0–106; 106 is STOP. */
    private const C128 = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    private const START_B = 104;

    private const START_C = 105;

    private const CODE_B = 100;

    private const CODE_C = 99;

    private const STOP = 106;

    private const EAN_L = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'];

    private const EAN_G = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'];

    private const EAN_R = ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'];

    private const EAN_PARITY = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL'];

    /**
     * Pick the symbology for a value: a valid EAN-13 (or 12 digits we can add
     * the check digit to) encodes as EAN-13, everything else as Code 128.
     *
     * @return array{symbology: string, value: string}
     */
    public function choose(string $value): array
    {
        $value = trim($value);
        if (preg_match('/^\d{13}$/', $value) && $this->eanCheckDigit(substr($value, 0, 12)) === (int) $value[12]) {
            return ['symbology' => 'ean13', 'value' => $value];
        }

        return ['symbology' => 'code128', 'value' => $this->printable($value)];
    }

    /** Module string for a value in the given symbology (quiet zones excluded). */
    public function modules(string $value, string $symbology): string
    {
        return $symbology === 'ean13' ? $this->ean13($value) : $this->code128($value);
    }

    /**
     * Vector SVG of the barcode, exactly $widthMm × $heightMm including the
     * quiet zones. The viewBox keeps the same aspect as the box, so renderers
     * that scale uniformly (DomPDF included) fit it edge to edge.
     */
    public function svg(string $value, string $symbology, float $widthMm, float $heightMm): string
    {
        $bits = str_repeat('0', self::QUIET).$this->modules($value, $symbology).str_repeat('0', self::QUIET);
        $total = strlen($bits);
        $h = round($total * $heightMm / max($widthMm, 0.1), 4);

        $rects = '';
        $run = 0;
        for ($i = 0; $i <= $total; $i++) {
            if ($i < $total && $bits[$i] === '1') {
                $run++;

                continue;
            }
            if ($run > 0) {
                $rects .= '<rect x="'.($i - $run).'" y="0" width="'.$run.'" height="'.$h.'"/>';
                $run = 0;
            }
        }

        // viewBox only — no width/height attributes. DomPDF's SVG renderer shrinks
        // an SVG that declares its own size (~0.7×); with just a viewBox it fills
        // the <img> box exactly, which is what makes bar widths land on printer dots.
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$total.' '.$h.'" '
            .'preserveAspectRatio="none" shape-rendering="crispEdges"><g fill="#000">'.$rects.'</g></svg>';
    }

    /** Width of one module in mm when the symbol (with quiet zones) fills $widthMm. */
    public function moduleWidthMm(string $value, string $symbology, float $widthMm): float
    {
        return $widthMm / (strlen($this->modules($value, $symbology)) + 2 * self::QUIET);
    }

    // ── Code 128 ─────────────────────────────────────────────────────────

    public function code128(string $value): string
    {
        $value = $this->printable($value);
        if ($value === '') {
            return '';
        }

        $codes = [];
        $set = null;
        foreach ($this->segments($value) as [$type, $text]) {
            if ($set === null) {
                $codes[] = $type === 'C' ? self::START_C : self::START_B;
            } elseif ($set !== $type) {
                $codes[] = $type === 'C' ? self::CODE_C : self::CODE_B;
            }
            $set = $type;
            if ($type === 'C') {
                foreach (str_split($text, 2) as $pair) {
                    $codes[] = (int) $pair;
                }
            } else {
                foreach (str_split($text) as $ch) {
                    $codes[] = ord($ch) - 32;
                }
            }
        }

        $sum = $codes[0];
        foreach (array_slice($codes, 1) as $i => $code) {
            $sum += ($i + 1) * $code;
        }
        $codes[] = $sum % 103;
        $codes[] = self::STOP;

        $bits = '';
        foreach ($codes as $code) {
            $bar = true;
            foreach (str_split(self::C128[$code]) as $w) {
                $bits .= str_repeat($bar ? '1' : '0', (int) $w);
                $bar = ! $bar;
            }
        }

        return $bits;
    }

    /**
     * Split into subset-B text and subset-C digit pairs. Digit runs of 4+ (or
     * a value that is all digits, even length) go to C, which halves their
     * width; an odd run leaves one digit in B so C always gets pairs.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function segments(string $value): array
    {
        $n = strlen($value);
        $out = [];
        $buf = '';
        $i = 0;
        while ($i < $n) {
            $run = strspn($value, '0123456789', $i);
            $whole = $i === 0 && $run === $n;
            if ($run >= 4 || ($whole && $run >= 2 && $run % 2 === 0)) {
                if ($run % 2 === 1) {
                    if ($i === 0) {
                        $out[] = ['C', substr($value, 0, $run - 1)];
                        $buf .= $value[$run - 1];
                        $i += $run;

                        continue;
                    }
                    $buf .= $value[$i];
                    $i++;
                    $run--;
                }
                if ($buf !== '') {
                    $out[] = ['B', $buf];
                    $buf = '';
                }
                $out[] = ['C', substr($value, $i, $run)];
                $i += $run;

                continue;
            }
            $take = max($run, 1);
            $buf .= substr($value, $i, $take);
            $i += $take;
        }
        if ($buf !== '') {
            $out[] = ['B', $buf];
        }

        // Merge neighbouring segments of the same subset.
        $merged = [];
        foreach ($out as $seg) {
            $last = array_key_last($merged);
            if ($last !== null && $merged[$last][0] === $seg[0]) {
                $merged[$last][1] .= $seg[1];
            } else {
                $merged[] = $seg;
            }
        }

        return $merged;
    }

    /** Code 128 subset B covers ASCII 32–126; anything else is dropped. */
    private function printable(string $value): string
    {
        return preg_replace('/[^\x20-\x7E]/', '', $value) ?? '';
    }

    // ── EAN-13 ───────────────────────────────────────────────────────────

    public function ean13(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) === 12) {
            $digits .= $this->eanCheckDigit($digits);
        }
        if (strlen($digits) !== 13) {
            return '';
        }

        $parity = self::EAN_PARITY[(int) $digits[0]];
        $bits = '101';
        for ($i = 1; $i <= 6; $i++) {
            $d = (int) $digits[$i];
            $bits .= $parity[$i - 1] === 'L' ? self::EAN_L[$d] : self::EAN_G[$d];
        }
        $bits .= '01010';
        for ($i = 7; $i <= 12; $i++) {
            $bits .= self::EAN_R[(int) $digits[$i]];
        }

        return $bits.'101';
    }

    public function eanCheckDigit(string $twelve): int
    {
        $sum = 0;
        foreach (str_split($twelve) as $i => $d) {
            $sum += (int) $d * ($i % 2 === 0 ? 1 : 3);
        }

        return (10 - $sum % 10) % 10;
    }
}
