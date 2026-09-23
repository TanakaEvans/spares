import FormField from '@/Components/FormField';

export default function SupplierForm({ data, setData, errors, currencies, onSubmit, processing, submitLabel }) {
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500';

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Identity</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField label="Supplier name" htmlFor="sf-name" required error={errors.name}>
                        <input id="sf-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Trading name" htmlFor="sf-trading" error={errors.trading_name}>
                        <input id="sf-trading" value={data.trading_name ?? ''} onChange={(e) => setData('trading_name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Type" htmlFor="sf-type" required error={errors.type}>
                        <select id="sf-type" value={data.type} onChange={(e) => setData('type', e.target.value)} className={input}>
                            {['local', 'import', 'manufacturer', 'distributor', 'wholesaler'].map((t) => (
                                <option key={t} value={t}>{t}</option>
                            ))}
                        </select>
                    </FormField>
                    <FormField label="VAT number" htmlFor="sf-vat" error={errors.vat_number}>
                        <input id="sf-vat" value={data.vat_number ?? ''} onChange={(e) => setData('vat_number', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Email" htmlFor="sf-email" error={errors.email}>
                        <input id="sf-email" type="email" value={data.email ?? ''} onChange={(e) => setData('email', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Phone" htmlFor="sf-phone" error={errors.phone}>
                        <input id="sf-phone" value={data.phone ?? ''} onChange={(e) => setData('phone', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="City" htmlFor="sf-city" error={errors.city}>
                        <input id="sf-city" value={data.city ?? ''} onChange={(e) => setData('city', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Country" htmlFor="sf-country" error={errors.country}>
                        <input id="sf-country" value={data.country ?? ''} onChange={(e) => setData('country', e.target.value)} className={input} />
                    </FormField>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Trading Terms</h2>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <FormField label="Payment terms (days)" htmlFor="sf-terms" required error={errors.payment_terms_days}>
                        <input id="sf-terms" type="number" value={data.payment_terms_days} onChange={(e) => setData('payment_terms_days', e.target.value)} className={`${input} text-right tabular-nums`} />
                    </FormField>
                    <FormField label="Lead time (days)" htmlFor="sf-lead" required error={errors.lead_time_days}>
                        <input id="sf-lead" type="number" value={data.lead_time_days} onChange={(e) => setData('lead_time_days', e.target.value)} className={`${input} text-right tabular-nums`} />
                    </FormField>
                    <FormField label="Min order value" htmlFor="sf-mov" error={errors.minimum_order_value}>
                        <input id="sf-mov" type="number" step="0.01" value={data.minimum_order_value ?? ''} onChange={(e) => setData('minimum_order_value', e.target.value)} className={`${input} text-right tabular-nums`} />
                    </FormField>
                    <FormField label="Currency" htmlFor="sf-currency" error={errors.currency_id}>
                        <select id="sf-currency" value={data.currency_id ?? ''} onChange={(e) => setData('currency_id', e.target.value)} className={input}>
                            <option value="">Base (USD)</option>
                            {currencies.map((c) => <option key={c.id} value={c.id}>{c.code}</option>)}
                        </select>
                    </FormField>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Banking (for payment runs)</h2>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <FormField label="Bank" htmlFor="sf-bank" error={errors.bank_name}>
                        <input id="sf-bank" value={data.bank_name ?? ''} onChange={(e) => setData('bank_name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Branch code" htmlFor="sf-bcode" error={errors.bank_branch_code}>
                        <input id="sf-bcode" value={data.bank_branch_code ?? ''} onChange={(e) => setData('bank_branch_code', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Account name" htmlFor="sf-bacc" error={errors.bank_account_name}>
                        <input id="sf-bacc" value={data.bank_account_name ?? ''} onChange={(e) => setData('bank_account_name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Account number" htmlFor="sf-bnum" error={errors.bank_account_number}>
                        <input id="sf-bnum" value={data.bank_account_number ?? ''} onChange={(e) => setData('bank_account_number', e.target.value)} className={input} />
                    </FormField>
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" disabled={processing} className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                    {processing ? 'Saving…' : submitLabel}
                </button>
            </div>
        </form>
    );
}
