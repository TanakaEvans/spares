import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ScanBarcode, Search, X } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import SearchInput from '@/Components/SearchInput';
import StatusBadge from '@/Components/StatusBadge';

export default function SerialsIndex({ serials, branches, filters }) {
    const { data, setData, post, processing, reset } = useForm({ part_id: '', branch_id: branches[0]?.id ?? '', serial: '', batch: '', reference: '' });
    const [picked, setPicked] = useState(null);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try { const r = await fetch(route('inventory.serials.part-lookup') + '?' + new URLSearchParams({ q: query }), { headers: { Accept: 'application/json' } }); setResults((await r.json()).parts ?? []); } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query]);

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    return (
        <ModuleLayout navConfig={navConfig} title="Serial & Batch Tracking" breadcrumbs={[{ label: 'Inventory', href: route('modules.show', 'inventory') }, { label: 'Serial & Batch Tracking' }]}>
            <Head title="Serial & Batch Tracking" />
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 space-y-4">
                    <SearchInput id="ser-search" value={filters.search ?? ''} onSearch={(s) => router.get(route('inventory.serials.index'), s ? { search: s } : {}, { preserveState: true, replace: true })} placeholder="Serial or batch…" className="w-72" />
                    <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                        {serials.data.length === 0 ? (
                            <div className="py-10 text-center text-sm text-slate-400"><ScanBarcode className="w-8 h-8 mx-auto mb-2 text-slate-300" />No serials recorded.</div>
                        ) : (
                            <table className="w-full text-sm">
                                <thead><tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100"><th className="px-4 py-2">Serial</th><th className="px-2 py-2">Batch</th><th className="px-2 py-2">Part</th><th className="px-2 py-2">Branch</th><th className="px-4 py-2">Status</th></tr></thead>
                                <tbody className="divide-y divide-slate-50">
                                    {serials.data.map((n) => (
                                        <tr key={n.id}>
                                            <td className="px-4 py-2 font-mono font-semibold text-slate-800">{n.serial}</td>
                                            <td className="px-2 py-2 text-slate-500">{n.batch ?? '—'}</td>
                                            <td className="px-2 py-2"><Link href={route('inventory.parts.show', n.part_id)} className="font-mono text-slate-600 hover:text-orange-600">{n.part_number}</Link></td>
                                            <td className="px-2 py-2 text-slate-500">{n.branch ?? '—'}</td>
                                            <td className="px-4 py-2"><StatusBadge status={n.status === 'in_stock' ? 'active' : n.status === 'sold' ? 'complete' : 'inactive'} label={n.status.replace('_', ' ')} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); post(route('inventory.serials.store'), { onSuccess: () => { reset('serial', 'batch', 'reference'); setPicked(null); } }); }} className="rounded-xl border border-slate-100 bg-white p-5 space-y-3 h-fit">
                    <h2 className="text-sm font-semibold text-slate-700">Record serial</h2>
                    {picked ? (
                        <div className="flex items-center justify-between rounded border border-slate-200 px-3 py-2 text-sm"><span className="font-mono">{picked.part_number}</span><button type="button" onClick={() => { setPicked(null); setData('part_id', ''); }} className="text-slate-300 hover:text-slate-600"><X className="w-4 h-4" /></button></div>
                    ) : (
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search part…" className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm" />
                            {results.length > 0 && <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">{results.map((p) => <li key={p.id}><button type="button" onClick={() => { setPicked(p); setData('part_id', p.id); setQuery(''); setResults([]); }} className="w-full px-3 py-2 text-left text-sm hover:bg-orange-50"><span className="font-mono font-semibold">{p.part_number}</span> <span className="text-slate-500">{p.description}</span></button></li>)}</ul>}
                        </div>
                    )}
                    <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>{branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}</select>
                    <input value={data.serial} onChange={(e) => setData('serial', e.target.value)} placeholder="Serial number" className={input} />
                    <input value={data.batch} onChange={(e) => setData('batch', e.target.value)} placeholder="Batch (optional)" className={input} />
                    <button type="submit" disabled={processing || !data.part_id || !data.serial} className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">Record</button>
                </form>
            </div>
        </ModuleLayout>
    );
}
