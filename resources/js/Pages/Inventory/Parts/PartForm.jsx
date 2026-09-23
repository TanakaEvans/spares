import FormField from '@/Components/FormField';

// The one part create/edit form (used by Create.jsx and Edit.jsx).
export default function PartForm({ data, setData, errors, categories, brands, units, onSubmit, processing, submitLabel }) {
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500';

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Identity</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <FormField label="Part number" htmlFor="pf-number" required error={errors.part_number} help="Your internal number — often the OEM or supplier number">
                        <input id="pf-number" value={data.part_number} onChange={(e) => setData('part_number', e.target.value.toUpperCase())} className={`${input} font-mono`} />
                    </FormField>
                    <FormField label="OEM number" htmlFor="pf-oem" error={errors.oem_number}>
                        <input id="pf-oem" value={data.oem_number ?? ''} onChange={(e) => setData('oem_number', e.target.value.toUpperCase())} className={`${input} font-mono`} />
                    </FormField>
                    <FormField label="Description" htmlFor="pf-desc" required error={errors.description} className="md:col-span-2">
                        <input id="pf-desc" value={data.description} onChange={(e) => setData('description', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Short description" htmlFor="pf-short" error={errors.short_description} help="For labels and POS display">
                        <input id="pf-short" value={data.short_description ?? ''} onChange={(e) => setData('short_description', e.target.value)} maxLength={100} className={input} />
                    </FormField>
                    <FormField label="Barcode (EAN)" htmlFor="pf-ean" error={errors.barcode_ean}>
                        <input id="pf-ean" value={data.barcode_ean ?? ''} onChange={(e) => setData('barcode_ean', e.target.value)} className={`${input} font-mono`} />
                    </FormField>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">Classification</h2>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <FormField label="Category" htmlFor="pf-cat" required error={errors.category_id}>
                        <select id="pf-cat" value={data.category_id ?? ''} onChange={(e) => setData('category_id', e.target.value)} className={input}>
                            <option value="">Select…</option>
                            {categories.map((c) => (
                                <option key={c.id} value={c.id}>{c.parent_id ? '  ' : ''}{c.name}</option>
                            ))}
                        </select>
                    </FormField>
                    <FormField label="Brand" htmlFor="pf-brand" error={errors.brand_id}>
                        <select id="pf-brand" value={data.brand_id ?? ''} onChange={(e) => setData('brand_id', e.target.value)} className={input}>
                            <option value="">—</option>
                            {brands.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Unit of measure" htmlFor="pf-unit" required error={errors.unit_id}>
                        <select id="pf-unit" value={data.unit_id ?? ''} onChange={(e) => setData('unit_id', e.target.value)} className={input}>
                            <option value="">Select…</option>
                            {units.map((u) => <option key={u.id} value={u.id}>{u.name} ({u.abbreviation})</option>)}
                        </select>
                    </FormField>
                    <FormField label="Weight (kg)" htmlFor="pf-weight" error={errors.weight_kg}>
                        <input id="pf-weight" type="number" step="0.001" value={data.weight_kg ?? ''} onChange={(e) => setData('weight_kg', e.target.value)} className={`${input} text-right tabular-nums`} />
                    </FormField>
                    <div className="flex items-end gap-6 md:col-span-2">
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" checked={!!data.is_oem} onChange={(e) => setData('is_oem', e.target.checked)} className="rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
                            OEM part
                        </label>
                        <label className="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" checked={!!data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
                            Active (sellable)
                        </label>
                    </div>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <FormField label="Notes" htmlFor="pf-notes" error={errors.notes}>
                    <textarea id="pf-notes" rows={3} value={data.notes ?? ''} onChange={(e) => setData('notes', e.target.value)} className={input} />
                </FormField>
            </div>

            <div className="flex justify-end gap-3">
                <button
                    type="button"
                    onClick={() => window.history.back()}
                    className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 transition-colors"
                >
                    {processing ? 'Saving…' : submitLabel}
                </button>
            </div>
        </form>
    );
}
