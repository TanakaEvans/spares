import FormField from '@/Components/FormField';
import RecordDialog from '@/Components/RecordDialog';

export const BODY_TYPES = ['pickup', 'sedan', 'hatchback', 'suv', 'van', 'mpv', 'wagon', 'truck', 'bus'];
const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

export const BLANK_MODEL = { make_id: '', name: '', body_type: '', generation: '', year_from: '', year_to: '', is_active: true };
export const modelValues = (m) => ({
    make_id: m.make_id, name: m.name, body_type: m.body_type ?? '', generation: m.generation ?? '',
    year_from: m.year_from ?? '', year_to: m.year_to ?? '', is_active: m.is_active ?? true,
});

export function ModelDialog({ editor, makes }) {
    const { form } = editor;
    return (
        <RecordDialog
            open={editor.isOpen}
            onClose={editor.close}
            dirty={form.isDirty}
            title={editor.isEdit ? `Edit ${editor.record.name}` : 'New vehicle model'}
            submitLabel={editor.isEdit ? 'Save changes' : 'Add model'}
            submitDisabled={!form.data.make_id || !form.data.name}
            processing={form.processing}
            onSubmit={() => editor.submit(route('vehicle-ref.models.store'), (m) => route('vehicle-ref.models.update', m.id))}
        >
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField label="Make" htmlFor="model-make" required error={form.errors.make_id}>
                    <select id="model-make" value={form.data.make_id} onChange={(e) => form.setData('make_id', e.target.value)} className={input}>
                        <option value="">Select make…</option>
                        {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                    </select>
                </FormField>
                <FormField label="Model name" htmlFor="model-name" required error={form.errors.name}>
                    <input id="model-name" autoFocus value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} className={input} />
                </FormField>
                <FormField label="Body type" htmlFor="model-body" error={form.errors.body_type}>
                    <select id="model-body" value={form.data.body_type} onChange={(e) => form.setData('body_type', e.target.value)} className={`${input} capitalize`}>
                        <option value="">—</option>
                        {BODY_TYPES.map((b) => <option key={b} value={b}>{b}</option>)}
                    </select>
                </FormField>
                <FormField label="Generation" htmlFor="model-gen" error={form.errors.generation} help="e.g. AN120, E170">
                    <input id="model-gen" value={form.data.generation} onChange={(e) => form.setData('generation', e.target.value)} className={input} />
                </FormField>
                <FormField label="Year from" htmlFor="model-yf" error={form.errors.year_from}>
                    <input id="model-yf" type="number" value={form.data.year_from} onChange={(e) => form.setData('year_from', e.target.value)} className={input} />
                </FormField>
                <FormField label="Year to" htmlFor="model-yt" error={form.errors.year_to} help="Blank = still in production">
                    <input id="model-yt" type="number" value={form.data.year_to} onChange={(e) => form.setData('year_to', e.target.value)} className={input} />
                </FormField>
            </div>
            {editor.isEdit && (
                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" checked={!!form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} className="rounded border-slate-300 text-orange-600" />
                    Active
                </label>
            )}
        </RecordDialog>
    );
}

export const BLANK_VARIANT = { name: '', engine_code: '', engine_size_cc: '', fuel_type: '', power_kw: '', transmission: '', drive: '', year_from: '', year_to: '' };
export const variantValues = (v) => Object.fromEntries(Object.keys(BLANK_VARIANT).map((k) => [k, v[k] ?? '']));

export function VariantDialog({ editor, model, engineCodes = [] }) {
    const { form } = editor;
    const select = (id, field, options, label) => (
        <FormField label={label} htmlFor={id} error={form.errors[field]}>
            <select id={id} value={form.data[field]} onChange={(e) => form.setData(field, e.target.value)} className={`${input} capitalize`}>
                <option value="">—</option>
                {options.map((o) => <option key={o} value={o}>{o}</option>)}
            </select>
        </FormField>
    );

    return (
        <RecordDialog
            open={editor.isOpen}
            onClose={editor.close}
            dirty={form.isDirty}
            size="lg"
            title={editor.isEdit ? `Edit variant ${editor.record.name}` : `New ${model.name} variant`}
            submitLabel={editor.isEdit ? 'Save changes' : 'Add variant'}
            submitDisabled={!form.data.name}
            processing={form.processing}
            onSubmit={() => editor.submit(route('vehicle-ref.models.variants.store', model.id), (v) => route('vehicle-ref.models.variants.update', [model.id, v.id]))}
        >
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div className="sm:col-span-2">
                    <FormField label="Variant name" htmlFor="var-name" required error={form.errors.name} help="e.g. 2.8 GD-6 4x4 AT">
                        <input id="var-name" autoFocus value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} className={input} />
                    </FormField>
                </div>
                <FormField label="Engine code" htmlFor="var-engine" error={form.errors.engine_code}>
                    <input id="var-engine" list="engine-code-options" value={form.data.engine_code} onChange={(e) => form.setData('engine_code', e.target.value.toUpperCase())} className={`${input} font-mono`} />
                    <datalist id="engine-code-options">{engineCodes.map((c) => <option key={c} value={c} />)}</datalist>
                </FormField>
                <FormField label="Size (cc)" htmlFor="var-cc" error={form.errors.engine_size_cc}>
                    <input id="var-cc" type="number" value={form.data.engine_size_cc} onChange={(e) => form.setData('engine_size_cc', e.target.value)} className={input} />
                </FormField>
                <FormField label="Power (kW)" htmlFor="var-kw" error={form.errors.power_kw}>
                    <input id="var-kw" type="number" value={form.data.power_kw} onChange={(e) => form.setData('power_kw', e.target.value)} className={input} />
                </FormField>
                {select('var-fuel', 'fuel_type', ['petrol', 'diesel', 'hybrid', 'electric', 'lpg'], 'Fuel')}
                {select('var-trans', 'transmission', ['manual', 'automatic', 'cvt', 'amt'], 'Transmission')}
                {select('var-drive', 'drive', ['4x2', '4x4', 'awd', 'fwd', 'rwd'], 'Drive')}
                <div className="grid grid-cols-2 gap-3">
                    <FormField label="From" htmlFor="var-yf" error={form.errors.year_from}>
                        <input id="var-yf" type="number" value={form.data.year_from} onChange={(e) => form.setData('year_from', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="To" htmlFor="var-yt" error={form.errors.year_to}>
                        <input id="var-yt" type="number" value={form.data.year_to} onChange={(e) => form.setData('year_to', e.target.value)} className={input} />
                    </FormField>
                </div>
            </div>
        </RecordDialog>
    );
}
