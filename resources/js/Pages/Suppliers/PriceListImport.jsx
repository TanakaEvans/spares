import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { FileUp, Upload } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import FormField from '@/Components/FormField';

/**
 * The price-list import wizard (Module 6.2):
 * Step 1 upload CSV → Step 2 map columns over a live preview → import.
 */
export default function PriceListImport({ supplier, preview = null }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title={`Import Price List — ${supplier.name}`}
            breadcrumbs={[
                { label: 'Suppliers', href: route('modules.show', 'suppliers') },
                { label: supplier.name, href: route('suppliers.show', supplier.id) },
                { label: 'Import Price List' },
            ]}
        >
            <Head title="Import Price List" />
            <div className="max-w-4xl space-y-6">
                {preview === null ? <UploadStep supplier={supplier} /> : <MapStep supplier={supplier} preview={preview} />}
            </div>
        </ModuleLayout>
    );
}

function UploadStep({ supplier }) {
    const [file, setFile] = useState(null);
    const [uploading, setUploading] = useState(false);

    function upload(e) {
        e.preventDefault();
        if (!file) return;
        setUploading(true);
        router.post(route('suppliers.pricelists.preview', supplier.id), { file }, {
            forceFormData: true,
            onFinish: () => setUploading(false),
        });
    }

    return (
        <form onSubmit={upload} className="bg-white rounded-xl shadow-sm border border-slate-100 p-8">
            <h1 className="text-xl font-bold text-slate-900 mb-1">Step 1 — Upload the supplier's CSV</h1>
            <p className="text-sm text-slate-500 mb-6">
                First row must be column headers. You will map their columns to ours before anything imports.
                Parts are matched by our part number, OEM number and cross-references.
            </p>
            <label className="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-slate-300 py-12 cursor-pointer hover:border-orange-400 hover:bg-orange-50/40 transition-colors">
                <FileUp className="w-8 h-8 text-slate-400" />
                <span className="text-sm text-slate-600">
                    {file ? <strong>{file.name}</strong> : 'Click to choose a .csv file'}
                </span>
                <input type="file" accept=".csv,.txt" className="hidden" onChange={(e) => setFile(e.target.files[0] ?? null)} />
            </label>
            <div className="mt-6 flex justify-end">
                <button
                    type="submit"
                    disabled={!file || uploading}
                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                >
                    <Upload className="w-4 h-4" />
                    {uploading ? 'Uploading…' : 'Upload & Preview'}
                </button>
            </div>
        </form>
    );
}

function MapStep({ supplier, preview }) {
    const { data, setData, post, processing, errors } = useForm({
        path: preview.path,
        name: `${supplier.name} — ${new Date().toISOString().slice(0, 10)}`,
        map: {
            supplier_part_number: preview.guess.supplier_part_number ?? '',
            cost_price: preview.guess.cost_price ?? '',
            description: preview.guess.description ?? '',
        },
        activate: true,
    });

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const columnOptions = preview.header.map((h, i) => ({ value: i, label: `${i + 1}: ${h}` }));

    function setMap(key, value) {
        setData('map', { ...data.map, [key]: value === '' ? '' : Number(value) });
    }

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                post(route('suppliers.pricelists.store', supplier.id));
            }}
            className="space-y-6"
        >
            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h1 className="text-xl font-bold text-slate-900 mb-1">Step 2 — Map their columns to ours</h1>
                <p className="text-sm text-slate-500 mb-5">Best guesses are pre-selected — check them against the preview below.</p>
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <FormField label="List name" htmlFor="pl-name" required error={errors.name}>
                        <input id="pl-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Their part number column" htmlFor="pl-map-num" required error={errors['map.supplier_part_number']}>
                        <select id="pl-map-num" value={data.map.supplier_part_number} onChange={(e) => setMap('supplier_part_number', e.target.value)} className={input}>
                            <option value="">Select…</option>
                            {columnOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Cost price column" htmlFor="pl-map-price" required error={errors['map.cost_price']}>
                        <select id="pl-map-price" value={data.map.cost_price} onChange={(e) => setMap('cost_price', e.target.value)} className={input}>
                            <option value="">Select…</option>
                            {columnOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                        </select>
                    </FormField>
                    <FormField label="Description column" htmlFor="pl-map-desc" error={errors['map.description']}>
                        <select id="pl-map-desc" value={data.map.description} onChange={(e) => setMap('description', e.target.value)} className={input}>
                            <option value="">— skip —</option>
                            {columnOptions.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                        </select>
                    </FormField>
                </div>
                <label className="mt-4 flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" checked={data.activate} onChange={(e) => setData('activate', e.target.checked)} className="rounded border-slate-300 text-orange-600" />
                    Activate immediately (supersedes the current active list)
                </label>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto">
                <h2 className="px-6 pt-4 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">File preview (first {preview.rows.length} rows)</h2>
                <table className="w-full text-xs">
                    <thead>
                        <tr className="text-left text-slate-400 border-b border-slate-100">
                            {preview.header.map((h, i) => (
                                <th key={i} className={`px-3 py-2 first:pl-6 ${
                                    [data.map.supplier_part_number, data.map.cost_price].includes(i) ? 'bg-orange-50 text-orange-700' : ''
                                }`}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50">
                        {preview.rows.map((row, r) => (
                            <tr key={r}>
                                {row.map((cell, c) => (
                                    <td key={c} className={`px-3 py-1.5 first:pl-6 text-slate-600 ${
                                        [data.map.supplier_part_number, data.map.cost_price].includes(c) ? 'bg-orange-50/50 font-medium' : ''
                                    }`}>{cell}</td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex justify-end gap-3">
                <button type="button" onClick={() => router.visit(route('suppliers.pricelists.import', supplier.id))} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                    Start over
                </button>
                <button
                    type="submit"
                    disabled={processing || data.map.supplier_part_number === '' || data.map.cost_price === ''}
                    className="rounded-lg bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                >
                    {processing ? 'Importing…' : 'Import Price List'}
                </button>
            </div>
        </form>
    );
}
