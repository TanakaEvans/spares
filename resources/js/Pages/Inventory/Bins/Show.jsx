import { Head } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import MoneyDisplay from '@/Components/MoneyDisplay';
import RelatedLinks from '@/Components/RelatedLinks';
import StatusBadge from '@/Components/StatusBadge';

// Read-only: what is physically stored in one bin. Stock only changes
// through posted documents, never from here.
export default function BinShow({ bin, levels, totals }) {
    const title = `Bin ${bin.code}`;

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={title}
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Bin Locations', href: route('inventory.bins.index') },
                { label: bin.code },
            ]}
        >
            <Head title={title} />

            <div className="space-y-5">
                <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 bg-slate-600 rounded-xl flex items-center justify-center">
                                <MapPin className="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2.5 flex-wrap">
                                    <h1 className="font-mono text-2xl font-bold text-slate-900">{bin.code}</h1>
                                    <StatusBadge status={bin.is_active ? 'active' : 'inactive'} />
                                </div>
                                <p className="text-sm text-slate-500 mt-0.5">
                                    <EntityLink type="branch" id={bin.branch_id} label={bin.branch} />
                                    {bin.branch_code ? <span className="font-mono text-slate-400"> · {bin.branch_code}</span> : null}
                                    {bin.notes ? <span className="text-slate-400"> · {bin.notes}</span> : null}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-4 grid grid-cols-3 gap-4 border-t border-slate-100 pt-4 text-sm">
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Parts stored</div>
                            <div className="text-lg font-bold tabular-nums text-slate-800">{totals.parts}</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Units on hand</div>
                            <div className="text-lg font-bold tabular-nums text-slate-800">{totals.qty_on_hand}</div>
                        </div>
                        <div>
                            <div className="text-xs text-slate-400 uppercase tracking-wide">Value at AVCO</div>
                            <div className="text-lg font-bold tabular-nums text-slate-800"><MoneyDisplay amount={totals.stock_value} /></div>
                        </div>
                    </div>
                </div>

                <DataTable
                    columns={[
                        { key: 'part_number', label: 'Part No', render: (l) => <EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" /> },
                        { key: 'description', label: 'Description', render: (l) => <span className="text-slate-600">{l.description}</span> },
                        { key: 'qty_on_hand', label: 'On hand', align: 'right' },
                        { key: 'qty_reserved', label: 'Reserved', align: 'right', render: (l) => <span className="text-slate-400">{l.qty_reserved}</span> },
                        {
                            key: 'qty_available', label: 'Available', align: 'right',
                            render: (l) => <span className={l.qty_available > 0 ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold'}>{l.qty_available}</span>,
                        },
                        { key: 'average_cost', label: 'AVCO', align: 'right', render: (l) => <MoneyDisplay amount={l.average_cost} /> },
                        { key: 'stock_value', label: 'Value', align: 'right', render: (l) => <MoneyDisplay amount={l.stock_value} /> },
                        { key: 'last_movement_at', label: 'Last movement', render: (l) => <span className="tabular-nums text-slate-400">{l.last_movement_at ?? '—'}</span> },
                    ]}
                    rows={levels}
                    rowHref={(l) => route('inventory.parts.show', l.part_id)}
                    emptyTitle="Nothing stored in this bin"
                    emptyMessage="Assign a bin to a part's stock level and it will appear here."
                />

                <RelatedLinks
                    groups={[
                        { title: 'Branch', items: [{ type: 'branch', id: bin.branch_id, label: bin.branch }] },
                        { title: 'Parts in this bin', items: levels.map((l) => ({ type: 'part', id: l.part_id, label: l.part_number, mono: true })) },
                    ]}
                />
            </div>
        </ModuleLayout>
    );
}
