<?php

namespace App\Services;

use App\Models\VatReturn;
use Illuminate\Support\Facades\DB;

/**
 * VAT returns (Module 7.6). Output VAT (2210) less Input VAT (2220) over a
 * period, computed live from posted journals — never from cached values, so a
 * return always reconciles to the ledger it reports on.
 */
class VatReturnService
{
    public function __construct(private readonly NumberSequenceService $sequences)
    {
    }

    /** Movement on a VAT account within a period, in its natural direction. */
    private function movement(string $accountCode, string $from, string $to): float
    {
        $row = DB::table('gl_journal_lines as l')
            ->join('gl_journals as j', 'j.id', '=', 'l.journal_id')
            ->join('gl_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('j.status', 'posted')
            ->where('a.account_code', $accountCode)
            ->whereDate('j.journal_date', '>=', $from)
            ->whereDate('j.journal_date', '<=', $to)
            ->selectRaw('COALESCE(SUM(l.debit),0) d, COALESCE(SUM(l.credit),0) c')
            ->first();

        // 2210 is credit-normal (output), 2220 debit-normal (input).
        return $accountCode === '2210'
            ? round((float) $row->c - (float) $row->d, 2)
            : round((float) $row->d - (float) $row->c, 2);
    }

    public function compute(string $from, string $to): array
    {
        $output = $this->movement('2210', $from, $to);
        $input = $this->movement('2220', $from, $to);

        return [
            'period_start' => $from,
            'period_end' => $to,
            'output_vat' => $output,
            'input_vat' => $input,
            'net_payable' => round($output - $input, 2),
        ];
    }

    public function generate(string $from, string $to, ?int $userId = null): VatReturn
    {
        $c = $this->compute($from, $to);

        return VatReturn::create([
            'reference' => 'VAT-'.\Illuminate\Support\Carbon::parse($from)->format('Ym'),
            'period_start' => $from,
            'period_end' => $to,
            'output_vat' => $c['output_vat'],
            'input_vat' => $c['input_vat'],
            'net_payable' => $c['net_payable'],
            'status' => 'draft',
            'prepared_by' => $userId,
        ]);
    }
}
