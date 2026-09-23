import { useForm } from '@inertiajs/react';
import FormField from '@/Components/FormField';

const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500';

/**
 * Shared create/edit form for a customer. `customer` present → edit mode.
 * Kept in one place so the two screens never drift (UI-13 consistency).
 */
export default function CustomerForm({ customer = null, groups, priceLists, types, action, method = 'post', submitLabel }) {
    const { data, setData, post, patch, processing, errors } = useForm({
        type: customer?.type ?? 'individual',
        name: customer?.name ?? '',
        trading_name: customer?.trading_name ?? '',
        vat_number: customer?.vat_number ?? '',
        email: customer?.email ?? '',
        phone: customer?.phone ?? '',
        address: customer?.address ?? '',
        city: customer?.city ?? '',
        customer_group_id: customer?.customer_group_id ?? '',
        price_list_id: customer?.price_list_id ?? '',
        payment_terms_days: customer?.payment_terms_days ?? 0,
        credit_limit: customer?.credit_limit ?? 0,
        is_active: customer?.is_active ?? true,
        notes: customer?.notes ?? '',
    });

    const defaultList = priceLists.find((p) => p.is_default);

    function submit(e) {
        e.preventDefault();
        (method === 'patch' ? patch : post)(action);
    }

    return (
        <form onSubmit={submit} className="max-w-3xl space-y-6">
            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Identity</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField label="Type" htmlFor="c-type" required error={errors.type}>
                        <select id="c-type" value={data.type} onChange={(e) => setData('type', e.target.value)} className={input}>
                            {types.map((t) => <option key={t} value={t} className="capitalize">{t}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Name" htmlFor="c-name" required error={errors.name}>
                        <input id="c-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Trading name" htmlFor="c-trading" error={errors.trading_name}>
                        <input id="c-trading" value={data.trading_name} onChange={(e) => setData('trading_name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="VAT number" htmlFor="c-vat" error={errors.vat_number}>
                        <input id="c-vat" value={data.vat_number} onChange={(e) => setData('vat_number', e.target.value)} className={input} />
                    </FormField>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Contact</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField label="Email" htmlFor="c-email" error={errors.email}>
                        <input id="c-email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Phone" htmlFor="c-phone" error={errors.phone}>
                        <input id="c-phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="City" htmlFor="c-city" error={errors.city}>
                        <input id="c-city" value={data.city} onChange={(e) => setData('city', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Address" htmlFor="c-address" error={errors.address}>
                        <input id="c-address" value={data.address} onChange={(e) => setData('address', e.target.value)} className={input} />
                    </FormField>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6 space-y-4">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Pricing &amp; Credit</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField label="Customer group" htmlFor="c-group" error={errors.customer_group_id}>
                        <select id="c-group" value={data.customer_group_id ?? ''} onChange={(e) => setData('customer_group_id', e.target.value)} className={input}>
                            <option value="">None</option>
                            {groups.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Price list override" htmlFor="c-pl" error={errors.price_list_id}
                        help={`Falls back to the group's list, then ${defaultList?.name ?? 'the default retail list'}.`}>
                        <select id="c-pl" value={data.price_list_id ?? ''} onChange={(e) => setData('price_list_id', e.target.value)} className={input}>
                            <option value="">Use group / default</option>
                            {priceLists.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Payment terms (days)" htmlFor="c-terms" required error={errors.payment_terms_days}
                        help="0 = cash on delivery (no account).">
                        <input id="c-terms" type="number" min="0" max="365" value={data.payment_terms_days}
                            onChange={(e) => setData('payment_terms_days', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Credit limit" htmlFor="c-limit" required error={errors.credit_limit}>
                        <input id="c-limit" type="number" min="0" step="0.01" value={data.credit_limit}
                            onChange={(e) => setData('credit_limit', e.target.value)} className={input} />
                    </FormField>
                </div>
                <FormField label="Notes" htmlFor="c-notes" error={errors.notes}>
                    <textarea id="c-notes" rows={2} value={data.notes} onChange={(e) => setData('notes', e.target.value)} className={input} />
                </FormField>
            </div>

            <div className="flex justify-end gap-3">
                <button type="button" onClick={() => window.history.back()} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit" disabled={processing || !data.name}
                    className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                    {processing ? 'Saving…' : submitLabel}
                </button>
            </div>
        </form>
    );
}
