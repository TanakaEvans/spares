import FormField from '@/Components/FormField';
import RecordDialog from '@/Components/RecordDialog';

export const BLANK_BRAND = { name: '', code: '', country_of_origin: '', is_oem_brand: false, is_active: true };
export const brandValues = (b) => ({
    name: b.name, code: b.code, country_of_origin: b.country_of_origin ?? '',
    is_oem_brand: !!b.is_oem_brand, is_active: !!b.is_active,
});

/** Create/edit a part brand — shared by the list and the detail page. */
export default function BrandDialog({ editor }) {
    const { form } = editor;
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <RecordDialog
            open={editor.isOpen}
            onClose={editor.close}
            dirty={form.isDirty}
            title={editor.isEdit ? `Edit ${editor.record.name}` : 'New part brand'}
            submitLabel={editor.isEdit ? 'Save changes' : 'Add brand'}
            submitDisabled={!form.data.name || !form.data.code}
            processing={form.processing}
            onSubmit={() => editor.submit(route('inventory.brands.store'), (b) => route('inventory.brands.update', b.id))}
        >
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <FormField label="Name" htmlFor="brand-name" required error={form.errors.name}>
                    <input id="brand-name" autoFocus value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} className={input} />
                </FormField>
                <FormField label="Code" htmlFor="brand-code" required error={form.errors.code} help="Letters/numbers, e.g. BOSCH">
                    <input id="brand-code" value={form.data.code} onChange={(e) => form.setData('code', e.target.value.toUpperCase())} className={`${input} font-mono`} maxLength={20} />
                </FormField>
                <FormField label="Country of origin" htmlFor="brand-country" error={form.errors.country_of_origin}>
                    <input id="brand-country" value={form.data.country_of_origin} onChange={(e) => form.setData('country_of_origin', e.target.value)} className={input} />
                </FormField>
            </div>
            <label className="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" checked={form.data.is_oem_brand} onChange={(e) => form.setData('is_oem_brand', e.target.checked)} className="rounded border-slate-300 text-orange-600" />
                OEM brand (vehicle manufacturer's genuine parts)
            </label>
            {editor.isEdit && (
                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} className="rounded border-slate-300 text-orange-600" />
                    Active — inactive brands can't be picked on new parts
                </label>
            )}
        </RecordDialog>
    );
}
