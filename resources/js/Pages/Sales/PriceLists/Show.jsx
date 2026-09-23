import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Star, Trash2 } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/sales';
import DataTable from '@/Components/DataTable';
import SearchInput from '@/Components/SearchInput';

function PriceCell({ priceList, item }) {
    const [value, setValue] = useState(item.price);
    const [saving, setSaving] = useState(false);

    function save() {
        if (Number(value) === Number(item.price)) return;
        setSaving(true);
        router.patch(route('sales.price-lists.items.update', [priceList.id, item.id]), { price: value }, {
            preserveScroll: true, preserveState: true, onFinish: () => setSaving(false),
        });
    }

    return (
        <input
            type="number" step="0.01" min="0" value={value}
            aria-label={`Price for ${item.part_number}`}
            onChange={(e) => setValue(e.target.value)}
            onBlur={save}
            onKeyDown={(e) => e.key === 'Enter' && e.currentTarget.blur()}
            className={`w-28 rounded border px-2 py-1 text-right tabular-nums ${saving ? 'border-orange-300 bg-orange-50' : 'border-slate-300'}`}
        />
    );
}

function UnpricedRow({ priceList, part }) {
    const [price, setPrice] = useState('');
    const [saving, setSaving] = useState(false);

    function set() {
        if (!price) return;
        setSaving(true);
        router.post(route('sales.price-lists.items.store', priceList.id), { part_id: part.id, price }, {
            preserveScroll: true, onFinish: () => setSaving(false),
        });
    }

    return (
        <tr>
            <td className="px-4 py-2"><Link href={route('inventory.parts.show', part.id)} className="font-mono font-semibold text-slate-700 hover:text-orange-600">{part.part_number}</Link><span className="block text-xs text-slate-400">{part.description}</span></td>
            <td className="px-4 py-2 text-right">
                <div className="inline-flex items-center gap-2">
                    <input type="number" step="0.01" min="0" value={price} onChange={(e) => setPrice(e.target.value)}
                        aria-label={`Set price for ${part.part_number}`} placeholder="0.00"
                        className="w-24 rounded border border-slate-300 px-2 py-1 text-right tabular-nums" />
                    <button onClick={set} disabled={saving || !price} className="rounded-lg bg-orange-600 px-3 py-1 text-xs font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Set</button>
                </div>
            </td>
        </tr>
    );
}

export default function PriceListShow({ priceList, items, filters, unpricedParts }) {
    function removeItem(item) {
        router.delete(route('sales.price-lists.items.destroy', [priceList.id, item.id]), { preserveScroll: true });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title={priceList.name}
            breadcrumbs={[
                { label: 'Sales & POS', href: route('modules.show', 'sales') },
                { label: 'Price Lists', href: route('sales.price-lists.index') },
                { label: priceList.name },
            ]}
        >
            <Head title={priceList.name} />

            <div className="max-w-4xl space-y-6">
                <div className="flex items-center gap-3">
                    <h1 className="inline-flex items-center gap-2 text-xl font-bold text-slate-900">
                        {priceList.is_default && <Star className="w-5 h-5 text-amber-400 fill-amber-400" />}
                        {priceList.name}
                    </h1>
                    <span className="capitalize text-sm text-slate-400">{priceList.type}</span>
                </div>

                <div className="space-y-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-semibold text-slate-700">Priced parts</h2>
                        <SearchInput
                            id="pl-search"
                            value={filters.search ?? ''}
                            onSearch={(s) => router.get(route('sales.price-lists.show', priceList.id), s ? { search: s } : {}, { preserveState: true, replace: true })}
                            placeholder="Filter parts…"
                            className="w-64"
                        />
                    </div>
                    <DataTable
                        columns={[
                            { key: 'part_number', label: 'Part', render: (i) => <Link href={route('inventory.parts.show', i.part_id)} className="font-mono font-semibold text-slate-700 hover:text-orange-600">{i.part_number}</Link> },
                            { key: 'description', label: 'Description', render: (i) => <span className="text-slate-500">{i.description}</span> },
                            { key: 'price', label: 'Price (excl)', align: 'right', render: (i) => <PriceCell priceList={priceList} item={i} /> },
                            { key: 'actions', label: '', align: 'right', render: (i) => (
                                <button onClick={() => removeItem(i)} aria-label="Remove price" className="p-1 rounded text-slate-300 hover:text-red-600 hover:bg-red-50"><Trash2 className="w-4 h-4" /></button>
                            ) },
                        ]}
                        rows={items.data}
                        pagination={items}
                        emptyTitle="No prices yet"
                        emptyMessage="Add part prices below, or import them per supplier from the Suppliers module."
                    />
                </div>

                {priceList.is_default && unpricedParts.length > 0 && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50/40 overflow-hidden">
                        <div className="px-4 py-3 border-b border-amber-200 bg-amber-50">
                            <h2 className="text-sm font-semibold text-amber-800">Unpriced parts ({unpricedParts.length})</h2>
                            <p className="text-xs text-amber-700">These active parts have no retail price and are blocked from sale until priced.</p>
                        </div>
                        <table className="w-full text-sm">
                            <tbody className="divide-y divide-amber-100">
                                {unpricedParts.map((p) => <UnpricedRow key={p.id} priceList={priceList} part={p} />)}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </ModuleLayout>
    );
}
