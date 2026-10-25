<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\DomainException;
use App\Http\Controllers\Concerns\ListQuery;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GlAccount;
use App\Models\GlJournal;
use App\Services\GlPostingService;
use App\Services\JournalSourceResolver;
use App\Support\ListExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class JournalController extends Controller
{
    use ListQuery;

    private function journalsQuery(Request $request): Builder
    {
        return $this->listApply(
            GlJournal::with('branch:id,name')
                ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('journal_type', $t)),
            $request,
            [
                'searchable' => ['journal_number', 'description', 'reference'],
                'sortable' => [
                    'journal_number' => 'journal_number', 'journal_date' => 'journal_date',
                    'journal_type' => 'journal_type', 'status' => 'status',
                ],
                'dateColumn' => 'journal_date',
                'defaultSort' => ['journal_date' => 'desc', 'id' => 'desc'],
            ]
        );
    }

    /** The filtered journal register as CSV or branded PDF (same filters as the grid). */
    public function export(Request $request): HttpResponse
    {
        $rows = $this->journalsQuery($request)->limit(5000)->get()->map(fn (GlJournal $j) => [
            $j->journal_number, $j->journal_date->toDateString(),
            ucwords(str_replace('_', ' ', $j->journal_type)),
            $j->description, $j->reference ?? '—',
            number_format((float) $j->lines()->sum('debit'), 2, '.', ''),
            ucfirst($j->status), $j->branch?->name ?? '—',
        ]);

        return ListExport::respond($request, 'journal-register', 'Journal Register',
            ['Journal No', 'Date', 'Type', 'Description', 'Reference', 'Total', 'Status', 'Branch'], $rows);
    }

    public function index(Request $request, JournalSourceResolver $sources): Response
    {
        $journals = $this->journalsQuery($request)
            ->paginate(25)
            ->withQueryString();

        $links = $sources->resolveMany($journals->getCollection());

        return Inertia::render('Finance/Journals/Index', [
            'journals' => $journals->through(fn (GlJournal $j) => [
                'id' => $j->id,
                'journal_number' => $j->journal_number,
                'journal_type' => $j->journal_type,
                'journal_date' => $j->journal_date->toDateString(),
                'description' => $j->description,
                'reference' => $j->reference,
                'status' => $j->status,
                'total' => (float) $j->lines()->sum('debit'),
                'source' => $links["{$j->source_type}:{$j->source_id}"] ?? null,
            ]),
            'filters' => $this->listFilters($request, ['type']),
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

    public function show(GlJournal $journal, JournalSourceResolver $sources): Response
    {
        $journal->load('lines.account:id,account_code,name', 'branch:id,name', 'postedBy:id,name', 'reversingJournal:id,journal_number', 'reversalOf:id,journal_number');

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
                'branch_id' => $journal->branch_id,
                'posted_by' => $journal->postedBy ? ['id' => $journal->postedBy->id, 'name' => $journal->postedBy->name] : null,
                'posted_at' => $journal->posted_at?->toDateTimeString(),
                'source' => $sources->resolve($journal->source_type, $journal->source_id),
                'reversing_journal' => $journal->reversingJournal ? ['id' => $journal->reversingJournal->id, 'label' => $journal->reversingJournal->journal_number] : null,
                'reversal_of' => $journal->reversalOf ? ['id' => $journal->reversalOf->id, 'label' => $journal->reversalOf->journal_number] : null,
                'is_reversible' => $journal->status === 'posted' && $journal->journal_type === 'manual',
                'lines' => $journal->lines->map(fn ($l) => [
                    'account_id' => $l->account_id,
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
