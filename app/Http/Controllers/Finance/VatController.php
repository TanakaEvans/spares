<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\VatReturn;
use App\Services\VatReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VatController extends Controller
{
    public function index(Request $request, VatReturnService $vat): Response
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->endOfMonth()->toDateString();

        return Inertia::render('Finance/Vat/Index', [
            'preview' => $vat->compute($from, $to),
            'returns' => VatReturn::orderByDesc('period_end')->limit(24)->get()
                ->map(fn (VatReturn $r) => [
                    'id' => $r->id,
                    'reference' => $r->reference,
                    'period' => $r->period_start->toDateString().' → '.$r->period_end->toDateString(),
                    'output_vat' => (float) $r->output_vat,
                    'input_vat' => (float) $r->input_vat,
                    'net_payable' => (float) $r->net_payable,
                    'status' => $r->status,
                ]),
            'filters' => ['from' => $from, 'to' => $to],
        ]);
    }

    public function generate(Request $request, VatReturnService $vat): RedirectResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $return = $vat->generate($data['from'], $data['to'], $request->user()->id);

        return back()->with('success', "VAT return {$return->reference} generated (net ".number_format((float) $return->net_payable, 2).').');
    }

    public function transition(Request $request, VatReturn $vatReturn): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', 'in:submit,pay']])['action'];

        $allowed = match ($action) {
            'submit' => $vatReturn->status === 'draft',
            'pay' => $vatReturn->status === 'submitted',
        };
        if (! $allowed) {
            return back()->withErrors(['action' => "Cannot {$action} a return that is {$vatReturn->status}."]);
        }

        $vatReturn->update([
            'status' => $action === 'submit' ? 'submitted' : 'paid',
            'submitted_at' => $action === 'submit' ? now() : $vatReturn->submitted_at,
        ]);

        return back()->with('success', "VAT return {$vatReturn->reference} marked {$vatReturn->status}.");
    }
}
