import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import DataTable from '@/Components/DataTable';
import EntityLink from '@/Components/EntityLink';
import MoneyDisplay from '@/Components/MoneyDisplay';
import ListToolbar from '@/Components/ListToolbar';
import { useListFilter } from '@/lib/listFilters';

export default function StockIndex({ levels, branches, filters, totalValue }) {
    const filter = useListFilter('inventory.stock.index', filters);

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Stock Levels"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Stock Levels' },
            ]}
        >
            <Head title="Stock Levels" />

            <div className="space-y-4">
                <ListToolbar
                    filters={filters}
                    onFilter={filter}
                    searchPlaceholder="Search part number or description…"
                    searchClass="w-72"
                    exportRoute="inventory.stock.export"
                >
                    <select
                        aria-label="Filter by branch"
                        value={filters.branch_id ?? ''}
                        onChange={(e) => filter({ branch_id: e.target.value })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">All branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            type="checkbox"
                            checked={!!filters.below_reorder}
                            onChange={(e) => filter({ below_reorder: e.target.checked ? 1 : '' })}
                            className="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                        />
                        Below reorder point only
                    </label>
                    <div className="text-sm text-slate-500 ml-auto">
                        Total stock value:{' '}
                        <span className="font-bold text-slate-800 tabular-nums">
                            <MoneyDisplay amount={totalValue} />
                        </span>
                    </div>
                </ListToolbar>

                <DataTable
                    columns={[
                        { key: 'part_number', label: 'Part No', sortable: 'part_number', render: (l) => <EntityLink type="part" id={l.part_id} label={l.part_number} mono className="font-semibold" /> },
                        { key: 'description', label: 'Description', sortable: 'description', render: (l) => <span className="text-slate-600">{l.description}</span> },
                        { key: 'branch', label: 'Branch', render: (l) => <EntityLink type="branch" id={l.branch_id} label={l.branch} muted /> },
                        { key: 'bin', label: 'Bin', render: (l) => <EntityLink type="bin" id={l.bin_id} label={l.bin ?? '—'} mono muted /> },
                        { key: 'qty_on_hand', label: 'On hand', align: 'right', sortable: 'qty_on_hand' },
                        { key: 'qty_reserved', label: 'Reserved', align: 'right', sortable: 'qty_reserved', render: (l) => <span className="text-slate-400">{l.qty_reserved}</span> },
                        {
                            key: 'qty_available', label: 'Available', align: 'right',
                            render: (l) => <span className={l.qty_available > 0 ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold'}>{l.qty_available}</span>,
                        },
                        { key: 'average_cost', label: 'AVCO', align: 'right', sortable: 'average_cost', render: (l) => <MoneyDisplay amount={l.average_cost} /> },
                        { key: 'stock_value', label: 'Value', align: 'right', render: (l) => <MoneyDisplay amount={l.stock_value} /> },
                        {
                            key: 'reorder_point', label: 'Reorder at', align: 'right',
                            render: (l) => (
                                <input
                                    type="number" step="1" min="0"
                                    defaultValue={l.reorder_point}
                                    aria-label={`Reorder point for ${l.part_number}`}
                                    title="Reorder point — saves on leaving the field"
                                    onClick={(e) => e.stopPropagation()}
                                    onBlur={(e) => {
                                        const v = Number(e.target.value) || 0;
                                        if (v !== l.reorder_point) {
                                            router.patch(route('inventory.stock.reorder-levels', l.id), {
                                                reorder_point: v,
                                                reorder_qty: Math.max(v * 2, 1),
                                            }, { preserveScroll: true, preserveState: true });
                                        }
                                    }}
                                    className="w-20 rounded-lg border border-slate-200 px-2 py-1 text-sm text-right tabular-nums hover:border-slate-300 focus:border-orange-500"
                                />
                            ),
                        },
                    ]}
                    rows={levels.data}
                    pagination={levels}
                    sorting={{ filters, onFilter: filter }}
                    rowHref={(l) => route('inventory.parts.show', l.part_id)}
                    emptyTitle="No stock records"
                    emptyMessage="Stock appears after opening balances or goods receipts are posted."
                />
            </div>
        </ModuleLayout>
    );
}
