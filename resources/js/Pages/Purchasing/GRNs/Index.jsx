import { Head, Link } from '@inertiajs/react';
import { PackageCheck } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/purchasing';
import DataTable from '@/Components/DataTable';
import MoneyDisplay from '@/Components/MoneyDisplay';
import StatusBadge from '@/Components/StatusBadge';

export default function GrnsIndex({ grns, awaiting }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Goods Receiving"
            breadcrumbs={[
                { label: 'Purchasing', href: route('modules.show', 'purchasing') },
                { label: 'Goods Receiving' },
            ]}
        >
            <Head title="Goods Receiving" />

            <div className="space-y-6">
                {awaiting.length > 0 && (
                    <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">
                            POs awaiting delivery ({awaiting.length})
                        </h2>
                        <div className="flex flex-wrap gap-2">
                            {awaiting.map((po) => (
                                <Link
                                    key={po.id}
                                    href={route('purchasing.grns.create', po.id)}
                                    className="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:border-orange-400 hover:bg-orange-50/50 transition-colors"
                                >
                                    <PackageCheck className="w-4 h-4 text-green-600" />
                                    <span className="font-mono font-semibold text-slate-700">{po.po_number}</span>
                                    <span className="text-slate-400">{po.supplier}</span>
                                    <StatusBadge status={po.status} />
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                <DataTable
                    columns={[
                        { key: 'grn_number', label: 'GRN No', render: (g) => <span className="font-mono font-semibold text-slate-800">{g.grn_number}</span> },
                        {
                            key: 'po_number', label: 'PO',
                            render: (g) => (
                                <Link href={route('purchasing.orders.show', g.po_id)} onClick={(e) => e.stopPropagation()} className="font-mono text-orange-700 hover:underline">
                                    {g.po_number}
                                </Link>
                            ),
                        },
                        { key: 'supplier', label: 'Supplier', render: (g) => <span className="text-slate-700">{g.supplier}</span> },
                        { key: 'branch', label: 'Branch', render: (g) => <span className="text-slate-500">{g.branch}</span> },
                        { key: 'received_date', label: 'Received', render: (g) => <span className="tabular-nums text-slate-500">{g.received_date}</span> },
                        { key: 'value', label: 'Value', align: 'right', render: (g) => <MoneyDisplay amount={g.value} /> },
                        { key: 'status', label: 'Status', render: (g) => <StatusBadge status={g.status} /> },
                    ]}
                    rows={grns.data}
                    pagination={grns}
                    emptyTitle="Nothing received yet"
                    emptyMessage="Receive goods against a submitted or confirmed purchase order."
                />
            </div>
        </ModuleLayout>
    );
}
