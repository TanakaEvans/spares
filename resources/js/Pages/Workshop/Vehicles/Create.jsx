import { useEffect, useRef, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Search, X, UserRound } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/workshop';
import FormField from '@/Components/FormField';

export default function VehicleCreate({ makes, preselectedCustomerId }) {
    const { data, setData, post, processing, errors } = useForm({
        customer_id: preselectedCustomerId ?? '',
        registration: '', vin: '', make_id: '', year: '', engine_code: '', colour: '',
    });
    const [customer, setCustomer] = useState(null);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const timer = useRef(null);

    useEffect(() => {
        clearTimeout(timer.current);
        if (query.trim().length < 2) { setResults([]); return; }
        timer.current = setTimeout(async () => {
            try {
                const res = await fetch(route('workshop.vehicles.customer-lookup') + '?' + new URLSearchParams({ q: query }), { headers: { Accept: 'application/json' } });
                setResults((await res.json()).customers ?? []);
            } catch { /* keep */ }
        }, 200);
        return () => clearTimeout(timer.current);
    }, [query]);

    function pickCustomer(c) {
        setCustomer(c); setData('customer_id', c.id); setQuery(''); setResults([]);
    }

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Register Vehicle"
            breadcrumbs={[
                { label: 'Workshop', href: route('modules.show', 'workshop') },
                { label: 'Vehicle Registry', href: route('workshop.vehicles.index') },
                { label: 'Register Vehicle' },
            ]}
        >
            <Head title="Register Vehicle" />

            <form onSubmit={(e) => { e.preventDefault(); post(route('workshop.vehicles.store')); }} className="max-w-2xl space-y-6">
                <div className="rounded-xl border border-slate-100 bg-white p-5">
                    <label className="block text-sm font-medium text-slate-700 mb-1">Owner <span className="text-red-500">*</span></label>
                    {customer ? (
                        <div className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                            <div className="flex items-center gap-2"><UserRound className="w-4 h-4 text-slate-400" /><span className="text-sm text-slate-700">{customer.name} <span className="text-slate-400">{customer.customer_number}</span></span></div>
                            <button type="button" onClick={() => { setCustomer(null); setData('customer_id', ''); }} aria-label="Change" className="p-1 text-slate-300 hover:text-slate-600"><X className="w-4 h-4" /></button>
                        </div>
                    ) : (
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Search customer…" aria-label="Search customer" className="w-full rounded-lg border border-slate-300 pl-9 pr-3 py-2 text-sm" />
                            {results.length > 0 && (
                                <ul className="absolute z-30 mt-1 w-full rounded-lg bg-white shadow-lg border border-slate-100 overflow-hidden">
                                    {results.map((c) => (
                                        <li key={c.id}><button type="button" onClick={() => pickCustomer(c)} className="w-full px-3 py-2 text-left text-sm hover:bg-orange-50">{c.name} <span className="text-xs text-slate-400">{c.customer_number}</span></button></li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}
                    {errors.customer_id && <p className="text-xs text-red-600 mt-1">{errors.customer_id}</p>}
                </div>

                <div className="rounded-xl border border-slate-100 bg-white p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField label="Registration" htmlFor="v-reg" required error={errors.registration}>
                        <input id="v-reg" value={data.registration} onChange={(e) => setData('registration', e.target.value.toUpperCase())} className={input} />
                    </FormField>
                    <FormField label="VIN" htmlFor="v-vin" error={errors.vin} help="17 characters if entered.">
                        <input id="v-vin" value={data.vin} onChange={(e) => setData('vin', e.target.value.toUpperCase())} maxLength={17} className={input} />
                    </FormField>
                    <FormField label="Make" htmlFor="v-make" error={errors.make_id}>
                        <select id="v-make" value={data.make_id} onChange={(e) => setData('make_id', e.target.value)} className={input}>
                            <option value="">—</option>
                            {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Year" htmlFor="v-year" error={errors.year}>
                        <input id="v-year" type="number" min="1950" max={new Date().getFullYear() + 1} value={data.year} onChange={(e) => setData('year', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Engine code" htmlFor="v-engine" error={errors.engine_code}>
                        <input id="v-engine" value={data.engine_code} onChange={(e) => setData('engine_code', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Colour" htmlFor="v-colour" error={errors.colour}>
                        <input id="v-colour" value={data.colour} onChange={(e) => setData('colour', e.target.value)} className={input} />
                    </FormField>
                </div>

                <div className="flex justify-end gap-3">
                    <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" disabled={processing || !data.customer_id || !data.registration} className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        {processing ? 'Saving…' : 'Register Vehicle'}
                    </button>
                </div>
            </form>
        </ModuleLayout>
    );
}
