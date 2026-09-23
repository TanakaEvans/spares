import { useEffect, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import FormField from '@/Components/FormField';

export default function StockTakeCreate({ branches }) {
    const { data, setData, post, processing, errors } = useForm({
        branch_id: branches[0]?.id ?? '', type: 'full', part_ids: [],
    });
    const [parts, setParts] = useState([]); // {id, part_number, description}
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (data.type !== 'spot' || query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try {
                const res = await fetch(route('sales.pos.part-lookup') + '?' + new URLSearchParams({ q: query }), { headers: { Accept: 'application/json' } });
                setResults((await res.json()).parts ?? []);
            } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query, data.type]);

    function addPart(p) {
        if (parts.some((x) => x.id === p.id)) return;
        const next = [...parts, p];
        setParts(next);
        setData('part_ids', next.map((x) => x.id));
        setQuery(''); setResults([]);
    }
    function removePart(id) {
        const next = parts.filter((x) => x.id !== id);
        setParts(next);
        setData('part_ids', next.map((x) => x.id));
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="New Stock Take"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Stock Takes', href: route('inventory.stock-takes.index') },
                { label: 'New Stock Take' },
            ]}
        >
            <Head title="New Stock Take" />

            <form onSubmit={(e) => { e.preventDefault(); post(route('inventory.stock-takes.store')); }} className="max-w-2xl space-y-6">
                <div className="rounded-xl border border-slate-100 bg-white p-6 space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField label="Branch" htmlFor="st-branch" required error={errors.branch_id}>
                            <select id="st-branch" value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>
                                {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </FormField>
                        <FormField label="Type" htmlFor="st-type" required error={errors.type}
                            help="Full = every part at the branch. Spot = only the parts you pick.">
                            <select id="st-type" value={data.type} onChange={(e) => setData('type', e.target.value)} className={input}>
                                <option value="full">Full count</option>
                                <option value="spot">Spot count</option>
                            </select>
                        </FormField>
                    </div>

                    {data.type === 'spot' && (
                        <div className="space-y-2">
                            <label className="block text-sm font-medium text-slate-700">Parts to count</label>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search part…" aria-label="Search part"
                                    className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm" />
                                {results.length > 0 && (
                                    <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">
                                        {results.map((p) => (
                                            <li key={p.id}>
                                                <button type="button" onClick={() => addPart(p)} className="w-full flex gap-3 px-3 py-2 text-sm text-left hover:bg-orange-50">
                                                    <span className="font-mono font-semibold text-slate-800">{p.part_number}</span>
                                                    <span className="flex-1 truncate text-slate-500">{p.description}</span>
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                            {parts.length > 0 && (
                                <ul className="flex flex-wrap gap-2">
                                    {parts.map((p) => (
                                        <li key={p.id} className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">
                                            {p.part_number}
                                            <button type="button" onClick={() => removePart(p.id)} aria-label={`Remove ${p.part_number}`}><X className="w-3 h-3" /></button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {errors['part_ids'] && <p className="text-xs text-red-600">{errors['part_ids']}</p>}
                        </div>
                    )}
                </div>

                <div className="flex justify-end gap-3">
                    <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" disabled={processing || (data.type === 'spot' && parts.length === 0)}
                        className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        {processing ? 'Starting…' : 'Start Count'}
                    </button>
                </div>
            </form>
        </ModuleLayout>
    );
}
