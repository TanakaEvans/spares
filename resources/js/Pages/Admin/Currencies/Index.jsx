import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Coins, Plus, TriangleAlert } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

function today() {
    return new Date().toISOString().slice(0, 10);
}

export default function CurrenciesIndex({ auth, currencies, rateHistory }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        currency_id: '',
        buy_rate: '',
        sell_rate: '',
        rate_date: today(),
    });

    const nonBase = currencies.filter((c) => !c.is_base);
    const activeMissingToday = nonBase.filter((c) => c.is_active && !(c.latest_rate && c.latest_rate.is_today));

    function submitRate(e) {
        e.preventDefault();
        post(route('admin.currencies.rates.store'), {
            preserveScroll: true,
            onSuccess: () => reset('buy_rate', 'sell_rate'),
        });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Currencies & Rates"
            breadcrumbs={[
                { label: 'System Administration', href: route('modules.show', 'system-admin') },
                { label: 'Currencies & Rates' },
            ]}
        >
            <Head title="Currencies & Rates" />

            <div className="space-y-6">
                <div className="bg-white rounded-xl shadow-sm p-6 border border-slate-100">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-gray-700 rounded-xl flex items-center justify-center">
                            <Coins className="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Currencies & Exchange Rates</h1>
                            <p className="text-slate-500 text-sm mt-0.5">
                                Rates are captured daily against the base currency. Historical documents always reprint
                                at their original rate.
                            </p>
                        </div>
                    </div>
                </div>

                {activeMissingToday.length > 0 && (
                    <div className="flex items-center gap-3 rounded-xl bg-amber-50 border border-amber-200 px-5 py-3.5 text-sm text-amber-800">
                        <TriangleAlert className="w-5 h-5 shrink-0" />
                        <span>
                            No rate captured <strong>today</strong> for:{' '}
                            {activeMissingToday.map((c) => c.code).join(', ')}. Sales may fall back to the last known
                            rate.
                        </span>
                    </div>
                )}

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Currencies table */}
                    <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
                        <h2 className="px-6 pt-5 pb-3 text-sm font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            Currencies
                        </h2>
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    <th className="px-6 py-3">Currency</th>
                                    <th className="px-3 py-3 text-right">Latest buy</th>
                                    <th className="px-3 py-3 text-right">Latest sell</th>
                                    <th className="px-3 py-3">Rate date</th>
                                    <th className="px-3 py-3">Status</th>
                                    <th className="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {currencies.map((c) => (
                                    <tr key={c.id} className="hover:bg-slate-50/60">
                                        <td className="px-6 py-3">
                                            <span className="font-mono font-semibold text-slate-800">{c.code}</span>
                                            <span className="text-slate-400 ml-2">{c.name}</span>
                                            {c.is_base && (
                                                <span className="ml-2 text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-orange-100 text-orange-700">
                                                    BASE
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums">
                                            {c.is_base ? '—' : c.latest_rate ? c.latest_rate.buy.toFixed(4) : '—'}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums">
                                            {c.is_base ? '—' : c.latest_rate ? c.latest_rate.sell.toFixed(4) : '—'}
                                        </td>
                                        <td className="px-3 py-3">
                                            {c.is_base ? (
                                                <span className="text-slate-300">—</span>
                                            ) : c.latest_rate ? (
                                                <span className={c.latest_rate.is_today ? 'text-green-600 font-medium' : 'text-amber-600'}>
                                                    {c.latest_rate.date}
                                                </span>
                                            ) : (
                                                <span className="text-slate-300">never</span>
                                            )}
                                        </td>
                                        <td className="px-3 py-3">
                                            <span
                                                className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                                                    c.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-500'
                                                }`}
                                            >
                                                {c.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-3 text-right">
                                            {!c.is_base && (
                                                <button
                                                    onClick={() => router.patch(route('admin.currencies.toggle', c.id), {}, { preserveScroll: true })}
                                                    className="text-xs font-medium text-slate-500 hover:text-orange-600"
                                                >
                                                    {c.is_active ? 'Deactivate' : 'Activate'}
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Rate capture + history */}
                    <div className="space-y-6">
                        <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                            <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">
                                Capture Daily Rate
                            </h2>
                            <form onSubmit={submitRate} className="space-y-4">
                                <div>
                                    <label htmlFor="rate-currency" className="text-sm font-medium text-slate-700">Currency</label>
                                    <select
                                        id="rate-currency"
                                        value={data.currency_id}
                                        onChange={(e) => setData('currency_id', e.target.value)}
                                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    >
                                        <option value="">Select currency…</option>
                                        {nonBase.filter((c) => c.is_active).map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.code} — {c.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.currency_id && <p className="text-xs text-red-600 mt-1">{errors.currency_id}</p>}
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label htmlFor="rate-buy" className="text-sm font-medium text-slate-700">Buy rate</label>
                                        <input
                                            id="rate-buy"
                                            type="number"
                                            step="0.000001"
                                            value={data.buy_rate}
                                            onChange={(e) => setData('buy_rate', e.target.value)}
                                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-right tabular-nums"
                                        />
                                        {errors.buy_rate && <p className="text-xs text-red-600 mt-1">{errors.buy_rate}</p>}
                                    </div>
                                    <div>
                                        <label htmlFor="rate-sell" className="text-sm font-medium text-slate-700">Sell rate</label>
                                        <input
                                            id="rate-sell"
                                            type="number"
                                            step="0.000001"
                                            value={data.sell_rate}
                                            onChange={(e) => setData('sell_rate', e.target.value)}
                                            className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-right tabular-nums"
                                        />
                                        {errors.sell_rate && <p className="text-xs text-red-600 mt-1">{errors.sell_rate}</p>}
                                    </div>
                                </div>
                                <div>
                                    <label htmlFor="rate-date" className="text-sm font-medium text-slate-700">Rate date</label>
                                    <input
                                        id="rate-date"
                                        type="date"
                                        value={data.rate_date}
                                        max={today()}
                                        onChange={(e) => setData('rate_date', e.target.value)}
                                        className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    />
                                    {errors.rate_date && <p className="text-xs text-red-600 mt-1">{errors.rate_date}</p>}
                                </div>
                                <button
                                    type="submit"
                                    disabled={processing || !data.currency_id || !data.buy_rate || !data.sell_rate}
                                    className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                >
                                    <Plus className="w-4 h-4" />
                                    Capture Rate
                                </button>
                            </form>
                        </div>

                        <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                            <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3">
                                Recent Rates
                            </h2>
                            {rateHistory.length === 0 ? (
                                <p className="text-sm text-slate-400">No rates captured yet.</p>
                            ) : (
                                <ul className="divide-y divide-slate-50 text-sm">
                                    {rateHistory.map((r) => (
                                        <li key={r.id} className="py-2 flex items-center justify-between">
                                            <span>
                                                <span className="font-mono font-semibold text-slate-700">{r.currency}</span>
                                                <span className="text-slate-400 ml-2">{r.date}</span>
                                            </span>
                                            <span className="tabular-nums text-slate-600">
                                                {r.buy.toFixed(4)} / {r.sell.toFixed(4)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
