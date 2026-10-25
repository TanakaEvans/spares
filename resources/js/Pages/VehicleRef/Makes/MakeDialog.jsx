import FormField from '@/Components/FormField';
import RecordDialog from '@/Components/RecordDialog';

export const BLANK_MAKE = { name: '', code: '', country_of_origin: '', sort_order: '', is_active: true };

export const makeValues = (m) => ({
    name: m.name, code: m.code, country_of_origin: m.country_of_origin ?? '',
    sort_order: m.sort_order ?? '', is_active: !!m.is_active,
});

/** Create/edit a vehicle make — shared by the list and the detail page. */
export default function MakeDialog({ editor }) {
    const { form } = editor;
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <RecordDialog
            open={editor.isOpen}
            onClose={editor.close}
            dirty={form.isDirty}
            title={editor.isEdit ? `Edit ${editor.record.name}` : 'New vehicle make'}
            submitLabel={editor.isEdit ? 'Save changes' : 'Add make'}
            submitDisabled={!form.data.name || !form.data.code}
            processing={form.processing}
            onSubmit={() => editor.submit(route('vehicle-ref.makes.store'), (m) => route('vehicle-ref.makes.update', m.id))}
        >
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField label="Name" htmlFor="make-name" required error={form.errors.name}>
                    <input id="make-name" autoFocus value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} className={input} />
                </FormField>
                <FormField label="Code" htmlFor="make-code" required error={form.errors.code} help="Short unique code, e.g. TOY">
                    <input id="make-code" value={form.data.code} onChange={(e) => form.setData('code', e.target.value.toUpperCase())} className={`${input} font-mono uppercase`} maxLength={10} />
                </FormField>
                <FormField label="Country of origin" htmlFor="make-country" error={form.errors.country_of_origin}>
                    <input id="make-country" value={form.data.country_of_origin} onChange={(e) => form.setData('country_of_origin', e.target.value)} className={input} />
                </FormField>
                <FormField label="Sort order" htmlFor="make-sort" error={form.errors.sort_order} help="Lower shows first in pickers">
                    <input id="make-sort" type="number" min="0" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', e.target.value)} className={input} />
                </FormField>
            </div>
            {editor.isEdit && (
                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} className="rounded border-slate-300 text-orange-600" />
                    Active — inactive makes are hidden from the fitment picker and new records
                </label>
            )}
        </RecordDialog>
    );
}
