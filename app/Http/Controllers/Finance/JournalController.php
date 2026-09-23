<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GlAccount;
use App\Models\GlJournal;
use App\Services\GlPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Finance/Journals/Index', [
            'journals' => GlJournal::with('branch:id,name')
                ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('journal_type', $t))
                ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                    fn ($x) => $x->where('journal_number', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")
                ))
                ->latest('journal_date')->latest('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (GlJournal $j) => [
                    'id' => $j->id,
                    'journal_number' => $j->journal_number,
                    'journal_type' => $j->journal_type,
                    'journal_date' => $j->journal_date->toDateString(),
                    'description' => $j->description,
                    'reference' => $j->reference,
                    'status' => $j->status,
                    'total' => (float) $j->lines()->sum('debit'),
                ]),
            'filters' => $request->only('search', 'type'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Finance/Journals/Create', [
            'accounts' => GlAccount::active()->where('allow_direct_posting', true)
                ->orderBy('account_code')->get(['id', 'account_code', 'name']),
            'branches' => Branch::where('status', 'active')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, GlPostingService $gl): RedirectResponse
    {
        $data = $request->validate([
            'journal_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account' => ['required', 'exists:gl_accounts,account_code'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $journal = $gl->post(
                journalType: 'manual',
                date: $data['journal_date'],
                description: $data['description'],
                lines: array_map(fn ($l) => [
                    'account' => $l['account'],
                    'debit' => (float) ($l['debit'] ?? 0),
                    'credit' => (float) ($l['credit'] ?? 0),
                    'description' => $l['description'] ?? null,
                ], $data['lines']),
                reference: $data['reference'] ?? null,
                branch: $data['branch_id'] ?? null,
                userId: $request->user()->id,
                fromSubLedger: false, // manual journals may NOT touch control accounts
            );
        } catch (DomainException $e) {
            return back()->withErrors(['journal' => $e->userMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['journal' => $e->getMessage()]);
        }

        return redirect()->route('finance.journals.show', $journal)
            ->with('success', "Journal {$journal->journal_number} posted.");
    }

    public function show(GlJournal $journal): Response
    {
        $journal->load('lines.account:id,account_code,name', 'branch:id,name');

        return Inertia::render('Finance/Journals/Show', [
            'journal' => [
                'id' => $journal->id,
                'journal_number' => $journal->journal_number,
                'journal_type' => $journal->journal_type,
                'journal_date' => $journal->journal_date->toDateString(),
                'description' => $journal->description,
                'reference' => $journal->reference,
                'status' => $journal->status,
                'branch' => $journal->branch?->name,
                'is_reversible' => $journal->status === 'posted' && $journal->journal_type === 'manual',
                'lines' => $journal->lines->map(fn ($l) => [
                    'account_code' => $l->account?->account_code,
                    'account_name' => $l->account?->name,
                    'description' => $l->description,
                    'debit' => (float) $l->debit,
                    'credit' => (float) $l->credit,
                ]),
            ],
        ]);
    }

    public function reverse(Request $request, GlJournal $journal, GlPostingService $gl): RedirectResponse
    {
        if ($journal->journal_type !== 'manual') {
            return back()->withErrors(['reverse' => 'Only manual journals can be reversed here; correct source documents at their origin.']);
        }

        try {
            $reversal = $gl->reverse($journal, $request->input('reason'), $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['reverse' => $e->getMessage()]);
        }

        return redirect()->route('finance.journals.show', $reversal)
            ->with('success', "Journal reversed by {$reversal->journal_number}.");
    }
}
