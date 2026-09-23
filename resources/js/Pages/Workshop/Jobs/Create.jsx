import { useEffect, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Search, X, CarFront } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import FormField from '@/Components/FormField';

export default function JobCreate({ branches, preselectedVehicleId }) {
    const { data, setData, post, processing, errors } = useForm({
        branch_id: branches[0]?.id ?? '',
        vehicle_id: preselectedVehicleId ?? '',
        customer_id: '',
        reported_fault: '',
        odometer_in: '',
        promised_at: '',
    });
    const [vehicle, setVehicle] = useState(null);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try {
                const res = await fetch(route('workshop.jobs.vehicle-lookup') + '?' + new URLSearchParams({ q: query }), { headers: { Accept: 'application/json' } });
                setResults((await res.json()).vehicles ?? []);
            } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query]);

    function pick(v) {
        setVehicle(v);
        setData((d) => ({ ...d, vehicle_id: v.id, customer_id: v.customer_id }));
        setQuery(''); setResults([]);
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title="New Job Card"
            breadcrumbs={[
                { label: 'Workshop', href: route('modules.show', 'workshop') },
                { label: 'Job Cards', href: route('workshop.jobs.index') },
                { label: 'New Job' },
            ]}>
            <Head title="New Job Card" />

            <form onSubmit={(e) => { e.preventDefault(); post(route('workshop.jobs.store')); }} className="max-w-2xl space-y-6">
                <div className="rounded-xl border border-slate-100 bg-white p-5">
                    <label className="block text-sm font-medium text-slate-700 mb-1">Vehicle</label>
                    {vehicle ? (
                        <div className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                            <div className="flex items-center gap-2"><CarFront className="w-4 h-4 text-slate-400" /><span className="text-sm text-slate-700">{vehicle.label} <span className="text-slate-400">· {vehicle.customer}</span></span></div>
                            <button type="button" onClick={() => { setVehicle(null); setData((d) => ({ ...d, vehicle_id: '', customer_id: '' })); }} aria-label="Change" className="p-1 text-slate-300 hover:text-slate-600"><X className="w-4 h-4" /></button>
                        </div>
                    ) : (
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search by registration or owner…" aria-label="Search vehicle" className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm" />
                            {results.length > 0 && (
                                <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">
                                    {results.map((v) => (
                                        <li key={v.id}><button type="button" onClick={() => pick(v)} className="w-full px-3 py-2 text-left text-sm hover:bg-orange-50"><span className="font-mono font-semibold text-slate-800">{v.registration}</span> <span className="text-slate-500">{v.label}</span> <span className="block text-xs text-slate-400">{v.customer}</span></button></li>
                                    ))}
                                </ul>
                            )}
                            <p className="mt-1 text-xs text-slate-400">A job can open without a vehicle, but needs one (and a customer) before it leaves “open”.</p>
                        </div>
                    )}
                </div>

                <div className="rounded-xl border border-slate-100 bg-white p-5 space-y-4">
                    <FormField label="Reported fault" htmlFor="j-fault" error={errors.reported_fault}>
                        <textarea id="j-fault" rows={3} value={data.reported_fault} onChange={(e) => setData('reported_fault', e.target.value)} className={input} placeholder="Customer's description of the problem…" />
                    </FormField>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <FormField label="Branch" htmlFor="j-branch" required error={errors.branch_id}>
                            <select id="j-branch" value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className={input}>
                                {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </FormField>
                        <FormField label="Odometer in" htmlFor="j-odo" error={errors.odometer_in}>
                            <input id="j-odo" type="number" min="0" value={data.odometer_in} onChange={(e) => setData('odometer_in', e.target.value)} className={input} />
                        </FormField>
                        <FormField label="Promised" htmlFor="j-promised" error={errors.promised_at}>
                            <input id="j-promised" type="datetime-local" value={data.promised_at} onChange={(e) => setData('promised_at', e.target.value)} className={input} />
                        </FormField>
                    </div>
                </div>

                <div className="flex justify-end gap-3">
                    <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" disabled={processing} className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                        {processing ? 'Opening…' : 'Open Job Card'}
                    </button>
                </div>
            </form>
        </ModuleLayout>
    );
}
