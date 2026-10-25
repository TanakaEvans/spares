import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import ConfirmDialog from '@/Components/ConfirmDialog';

/**
 * Create/edit lifecycle for a <RecordDialog>:
 *
 *   const editor = useRecordEditor({ name: '', code: '' });
 *   editor.open();                         // create
 *   editor.open(row, { name: row.name });  // edit
 *   editor.submit(route('x.store'), (r) => route('x.update', r.id));
 *   <RecordDialog open={editor.isOpen} onClose={editor.close} dirty={editor.form.isDirty} …>
 */
export function useRecordEditor(blank) {
    const form = useForm(blank);
    const [state, setState] = useState(null);

    function open(record = null, values = null) {
        const v = { ...blank, ...(values ?? {}) };
        form.setDefaults(v);
        form.setData(v);
        form.clearErrors();
        setState(record ? { mode: 'edit', record } : { mode: 'create' });
    }

    function close() {
        setState(null);
        form.clearErrors();
    }

    function submit(createUrl, updateUrl, extra = {}) {
        const opts = { preserveScroll: true, onSuccess: close, ...extra };
        if (state?.mode === 'edit') form.patch(updateUrl(state.record), opts);
        else form.post(createUrl, opts);
    }

    return { form, open, close, submit, isOpen: state !== null, isEdit: state?.mode === 'edit', record: state?.record ?? null };
}

/**
 * One delete-confirmation for a page. The server decides delete vs.
 * deactivate (in-use records are retired, never orphaned) and says which.
 *
 *   const { confirmDelete, deleteDialog } = useDeleteRecord();
 *   confirmDelete({ url: route('x.destroy', row.id), label: row.name });
 *   …{deleteDialog}
 */
export function useDeleteRecord() {
    const [target, setTarget] = useState(null);
    const [processing, setProcessing] = useState(false);

    const deleteDialog = (
        <ConfirmDialog
            open={target !== null}
            title={`Delete ${target?.label ?? 'this record'}?`}
            message={target?.message ?? 'If anything still uses it, it is deactivated instead so history stays intact.'}
            confirmLabel="Delete"
            destructive
            processing={processing}
            onConfirm={() => {
                setProcessing(true);
                router.delete(target.url, {
                    preserveScroll: true,
                    onFinish: () => { setProcessing(false); setTarget(null); },
                });
            }}
            onCancel={() => setTarget(null)}
        />
    );

    return { confirmDelete: setTarget, deleteDialog };
}
