import { useForm } from '@inertiajs/react';

/**
 * Shared config-form for the focused admin settings screens (password policy,
 * comms, print templates). Renders each declared setting by its type and PUTs
 * the whole set. `settings` = [{key,label,type,help,value}].
 */
export default function SettingsForm({ settings, action, submitLabel = 'Save changes' }) {
    const initial = Object.fromEntries(settings.map((s) => [s.key, s.value]));
    const { data, setData, put, processing, recentlySuccessful } = useForm(initial);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <form onSubmit={(e) => { e.preventDefault(); put(action); }} className="max-w-2xl rounded-xl border border-slate-100 bg-white p-6 space-y-5">
            {settings.map((s) => (
                <div key={s.key} className="grid grid-cols-1 md:grid-cols-3 gap-3 items-start">
                    <div className="md:col-span-1">
                        <label htmlFor={s.key} className="block text-sm font-medium text-slate-700">{s.label}</label>
                        {s.help && <p className="text-xs text-slate-400 mt-0.5">{s.help}</p>}
                    </div>
                    <div className="md:col-span-2">
                        {s.type === 'bool' ? (
                            <label className="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" id={s.key} checked={!!data[s.key]} onChange={(e) => setData(s.key, e.target.checked)} className="rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
                                {data[s.key] ? 'Enabled' : 'Disabled'}
                            </label>
                        ) : s.type === 'int' || s.type === 'decimal' || s.type === 'percent' || s.type === 'money' ? (
                            <input type="number" step={s.type === 'int' ? '1' : '0.01'} id={s.key} value={data[s.key] ?? ''} onChange={(e) => setData(s.key, e.target.value)} className={input} />
                        ) : (
                            <input type="text" id={s.key} value={data[s.key] ?? ''} onChange={(e) => setData(s.key, e.target.value)} className={input} />
                        )}
                    </div>
                </div>
            ))}
            <div className="flex items-center gap-3 pt-2 border-t border-slate-100">
                <button type="submit" disabled={processing} className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                    {processing ? 'Saving…' : submitLabel}
                </button>
                {recentlySuccessful && <span className="text-sm text-emerald-600">Saved.</span>}
            </div>
        </form>
    );
}
