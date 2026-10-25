import FormField from '@/Components/FormField';
import RecordDialog from '@/Components/RecordDialog';

export const FUELS = ['petrol', 'diesel', 'hybrid', 'electric', 'lpg'];
export const ASPIRATIONS = { naturally_aspirated: 'Naturally aspirated', turbo: 'Turbo', supercharged: 'Supercharged' };

export const BLANK_ENGINE = { code: '', make_id: '', description: '', capacity_cc: '', fuel_type: '', aspiration: '', cylinders: '' };

export const engineValues = (e) => ({
    code: e.code, make_id: e.make_id ?? '', description: e.description ?? '', capacity_cc: e.capacity_cc ?? '',
    fuel_type: e.fuel_type ?? '', aspiration: e.aspiration ?? '', cylinders: e.cylinders ?? '',
});

/** Create/edit an engine code — renaming carries its variants and fitments along (server-side). */
export default function EngineDialog({ editor, makes }) {
    const { form } = editor;
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const renaming = editor.isEdit && form.data.code !== editor.record.code;

    return (
        <RecordDialog
            open={editor.isOpen}
            onClose={editor.close}
            dirty={form.isDirty}
            title={editor.isEdit ? `Edit engine ${editor.record.code}` : 'New engine code'}
            submitLabel={editor.isEdit ? 'Save changes' : 'Add engine code'}
            submitDisabled={!form.data.code}
            processing={form.processing}
            onSubmit={() => editor.submit(route('vehicle-ref.engines.store'), (e) => route('vehicle-ref.engines.update', e.id))}
        >
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField label="Code" htmlFor="eng-code" required error={form.errors.code} help={renaming ? 'Renaming updates every vehicle variant and part fitment using it.' : 'e.g. 1GD-FTV'}>
                    <input id="eng-code" autoFocus value={form.data.code} onChange={(e) => form.setData('code', e.target.value.toUpperCase())} className={`${input} font-mono`} />
                </FormField>
                <FormField label="Make" htmlFor="eng-make" error={form.errors.make_id}>
                    <select id="eng-make" value={form.data.make_id} onChange={(e) => form.setData('make_id', e.target.value)} className={input}>
                        <option value="">— any / shared —</option>
                        {makes.map((m) => <option key={m.id} value={m.id}>{m.name}</option>)}
                    </select>
                </FormField>
            </div>
            <FormField label="Description" htmlFor="eng-desc" error={form.errors.description}>
                <input id="eng-desc" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} placeholder="e.g. 2.8L diesel turbo (Hilux/Fortuner 2015+)" className={input} />
            </FormField>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <FormField label="Capacity (cc)" htmlFor="eng-cc" error={form.errors.capacity_cc}>
                    <input id="eng-cc" type="number" min="1" value={form.data.capacity_cc} onChange={(e) => form.setData('capacity_cc', e.target.value)} className={input} />
                </FormField>
                <FormField label="Fuel" htmlFor="eng-fuel" error={form.errors.fuel_type}>
                    <select id="eng-fuel" value={form.data.fuel_type} onChange={(e) => form.setData('fuel_type', e.target.value)} className={`${input} capitalize`}>
                        <option value="">—</option>
                        {FUELS.map((f) => <option key={f} value={f}>{f}</option>)}
                    </select>
                </FormField>
                <FormField label="Aspiration" htmlFor="eng-asp" error={form.errors.aspiration}>
                    <select id="eng-asp" value={form.data.aspiration} onChange={(e) => form.setData('aspiration', e.target.value)} className={input}>
                        <option value="">—</option>
                        {Object.entries(ASPIRATIONS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                    </select>
                </FormField>
                <FormField label="Cylinders" htmlFor="eng-cyl" error={form.errors.cylinders}>
                    <input id="eng-cyl" type="number" min="1" max="16" value={form.data.cylinders} onChange={(e) => form.setData('cylinders', e.target.value)} className={input} />
                </FormField>
            </div>
        </RecordDialog>
    );
}
