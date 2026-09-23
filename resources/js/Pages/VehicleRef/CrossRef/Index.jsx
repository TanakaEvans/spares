import { Head, Link, router } from '@inertiajs/react';
import { ArrowRight, Shuffle } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/vehicle-reference';
import EmptyState from '@/Components/EmptyState';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

const TYPE_LABEL = { oem: 'OEM', aftermarket: 'Aftermarket', competitor: 'Competitor', ean: 'Barcode' };

// A customer arrives with ANY number — find our part (Module 8.3).
export default function CrossRefIndex({ query, results }) {
    function search(q) {
        router.get(route('vehicle-ref.cross-ref'), q ? { q } : {}, { preserveState: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Cross-Reference Search"
            breadcrumbs={[
                { label: 'Vehicle Reference', href: route('modules.show', 'vehicle-reference') },
                { label: 'Cross-Reference' },
            ]}
        >
            <Head title="Cross-Reference Search" />

            <div className="space-y-6 max-w-4xl">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h1 className="text-xl font-bold text-slate-900 mb-1">Cross-Reference Search</h1>
                    <p className="text-sm text-slate-500 mb-4">
                        Enter any number — OEM, aftermarket, barcode or our own — and find the part.
                        Superseded numbers resolve to their replacement automatically.
                    </p>
                    <SearchInput
                        id="crossref-search"
                        value={query}
                        onSearch={search}
                        placeholder="e.g. 90915-YZZD2, Z762, HU 7019 z, 04152-38020…"
                        autoFocus
                        className="max-w-xl"
                    />
                </div>

                {results !== null && results.length === 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100">
                        <EmptyState
                            icon={Shuffle}
                            title={`Nothing matches "${query}"`}
                            message="No part, OEM number, barcode or cross-reference matches. Check the number, or add the part to the catalogue."
                        />
                    </div>
                )}

                {results?.map((p) => (
                    <div key={p.id} className="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div className="min-w-0">
                                <div className="flex items-center gap-2 flex-wrap">
                                    <Link
                                        href={route('inventory.parts.show', p.id)}
                                        className="font-mono text-lg font-bold text-orange-700 hover:underline"
                                    >
                                        {p.part_number}
                                    </Link>
                                    {!p.is_active && <StatusBadge status="inactive" />}
                                </div>
                                <p className="text-sm text-slate-700">{p.description}</p>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    {p.brand}{p.category ? ` · ${p.category}` : ''}
                                </p>
                            </div>
                            <div className="text-right shrink-0">
                                {p.qty_available > 0 ? (
                                    <span className="text-green-700 font-semibold tabular-nums">{p.qty_available} in stock</span>
                                ) : (
                                    <span className="text-slate-400">out of stock</span>
                                )}
                            </div>
                        </div>

                        {p.superseded_by && (
                            <Link
                                href={route('inventory.parts.show', p.superseded_by.id)}
                                className="mt-3 flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-4 py-2.5 text-sm text-amber-800 hover:bg-amber-100 transition-colors"
                            >
                                Superseded — use{' '}
                                <span className="font-mono font-bold">{p.superseded_by.part_number}</span>
                                <span className="truncate">({p.superseded_by.description})</span>
                                <ArrowRight className="w-4 h-4 ml-auto shrink-0" />
                            </Link>
                        )}

                        {p.references.length > 0 && (
                            <div className="mt-3 flex flex-wrap gap-1.5">
                                {p.references.map((r, i) => (
                                    <span key={i} className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs">
                                        <span className="font-mono text-slate-700">{r.number}</span>
                                        <span className="text-slate-400">{r.brand ?? TYPE_LABEL[r.type] ?? r.type}</span>
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </ModuleLayout>
    );
}
