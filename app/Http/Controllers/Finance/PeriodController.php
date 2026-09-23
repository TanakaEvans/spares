<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\GlPeriod;
use App\Models\GlYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    public function index(): Response
    {
        $years = GlYear::with(['periods' => fn ($q) => $q->orderBy('period_number')])
            ->orderByDesc('start_date')->get()
            ->map(fn (GlYear $y) => [
                'id' => $y->id,
                'name' => $y->name,
                'status' => $y->status,
                'periods' => $y->periods->map(fn (GlPeriod $p) => [
                    'id' => $p->id,
                    'period_number' => $p->period_number,
                    'name' => $p->name,
                    'start_date' => $p->start_date->toDateString(),
                    'end_date' => $p->end_date->toDateString(),
                    'status' => $p->status,
                ]),
            ]);

        return Inertia::render('Finance/Periods/Index', ['years' => $years]);
    }

    public function transition(Request $request, GlPeriod $period): RedirectResponse
    {
        $action = $request->validate(['action' => ['required', 'in:close,reopen,lock']])['action'];

        $allowed = match ($action) {
            'close' => $period->status === 'open',
            'reopen' => $period->status === 'closed',
            'lock' => in_array($period->status, ['open', 'closed'], true),
        };

        if (! $allowed) {
            return back()->withErrors(['action' => "Cannot {$action} a period that is {$period->status}."]);
        }

        $period->update([
            'status' => match ($action) { 'close' => 'closed', 'reopen' => 'open', 'lock' => 'locked' },
            'closed_by' => in_array($action, ['close', 'lock'], true) ? $request->user()->id : null,
            'closed_at' => in_array($action, ['close', 'lock'], true) ? now() : null,
        ]);

        return back()->with('success', "Period {$period->name} ".match ($action) {
            'close' => 'closed.', 'reopen' => 'reopened.', 'lock' => 'locked — no further postings.',
        });
    }
}
