<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NumberSequence;
use App\Services\NumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NumberSequenceController extends Controller
{
    public function __construct(private readonly NumberSequenceService $sequences)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Sequences/Index', [
            'sequences' => NumberSequence::with('branch:id,name')
                ->orderBy('type')
                ->get()
                ->map(fn (NumberSequence $s) => [
                    'id' => $s->id,
                    'type' => $s->type,
                    'branch' => $s->branch?->name,
                    'prefix' => $s->prefix,
                    'include_date' => $s->include_date,
                    'date_format' => $s->date_format,
                    'padding' => $s->padding,
                    'reset_frequency' => $s->reset_frequency,
                    'next_number' => $s->next_number,
                    'preview' => $this->sequences->peek($s->type, $s->branch_id),
                ]),
        ]);
    }

    public function update(Request $request, NumberSequence $sequence): RedirectResponse
    {
        $data = $request->validate([
            'prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'include_date' => ['required', 'boolean'],
            'date_format' => ['required', 'in:Ymd,Ym,Y'],
            'padding' => ['required', 'integer', 'min:3', 'max:8'],
            'reset_frequency' => ['required', 'in:never,yearly,monthly'],
        ]);

        $sequence->update($data);

        return back()->with('success', "Sequence for {$sequence->type} updated.");
    }
}
