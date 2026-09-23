<?php

namespace App\Http\Controllers;

use App\Models\GlJournal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemController extends Controller
{
    /**
     * Activity log — the audited trail of posted financial events. Every
     * journal (sale, purchase, receipt, payment, adjustment, manual, opening)
     * records who posted it and when, with a link back to the source.
     */
    public function logs(Request $request): Response
    {
        $journals = GlJournal::query()
            ->with('branch:id,name')
            ->leftJoin('auth_users', 'auth_users.id', '=', 'gl_journals.posted_by')
            ->when($request->string('type')->toString(), fn ($q, $t) => $q->where('journal_type', $t))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                fn ($x) => $x->where('journal_number', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%")
            ))
            ->orderByDesc('gl_journals.posted_at')->orderByDesc('gl_journals.id')
            ->select('gl_journals.*', 'auth_users.name as posted_by_name')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (GlJournal $j) => [
                'id' => $j->id,
                'journal_number' => $j->journal_number,
                'type' => $j->journal_type,
                'description' => $j->description,
                'reference' => $j->reference,
                'branch' => $j->branch?->name,
                'user' => $j->posted_by_name ?? 'System',
                'at' => $j->posted_at?->toDateTimeString(),
            ]);

        return Inertia::render('System/Logs', [
            'journals' => $journals,
            'filters' => $request->only('search', 'type'),
            'types' => ['sales', 'purchase', 'receipt', 'payment', 'adjustment', 'manual', 'opening'],
        ]);
    }
}
