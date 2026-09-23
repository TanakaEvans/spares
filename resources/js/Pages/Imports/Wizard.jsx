import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Upload, FileSpreadsheet, ArrowRight, Download } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import customersNav from '@/nav/customers';
import suppliersNav from '@/nav/suppliers';
import inventoryNav from '@/nav/inventory';

const navByEntity = { customers: customersNav, suppliers: suppliersNav, parts: inventoryNav };

export default function ImportWizard({ entity, label, listRoute, exportRoute, fields, preview }) {
    const navConfig = navByEntity[entity] ?? inventoryNav;
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title={`Import ${label}`}
            breadcrumbs={[{ label, href: route(listRoute) }, { label: 'Import CSV' }]}>
            <Head title={`Import ${label}`} />
            <div className="max-w-3xl space-y-6">
                <ol className="flex items-center gap-2 text-xs font-medium text-slate-400">
                    <li className={!preview ? 'text-orange-600' : ''}>1. Upload</li>
                    <ArrowRight className="w-3 h-3" />
                    <li className={preview ? 'text-orange-600' : ''}>2. Map columns</li>
                    <ArrowRight className="w-3 h-3" />
                    <li>3. Done</li>
                </ol>

                {!preview ? (
                    <UploadStep entity={entity} label={label} exportRoute={exportRoute} fields={fields} input={input} />
                ) : (
                    <MapStep entity={entity} label={label} fields={fields} preview={preview} input={input} />
                )}
            </div>
        </ModuleLayout>
    );
}

function UploadStep({ entity, label, exportRoute, fields, input }) {
    const { data, setData, post, processing, errors } = useForm({ file: null });

    return (
        <form onSubmit={(e) => { e.preventDefault(); post(route('imports.preview', entity), { forceFormData: true }); }}
            className="rounded-xl border border-slate-100 bg-white p-6 space-y-4">
            <div className="flex items-start gap-3">
                <FileSpreadsheet className="w-6 h-6 text-orange-500 mt-0.5" />
                <div className="text-sm text-slate-600">
                    <p>Upload a <strong>CSV</strong> of {label.toLowerCase()}. You'll map its columns on the next step; rows are matched on their reference number, so re-importing corrects rather than duplicates.</p>
                    <p className="mt-1 text-slate-500">Columns you can map: {fields.map((f) => f.label.replace(/ \(.*\)/, '')).join(', ')}.</p>
                    <a href={route(exportRoute)} className="mt-2 inline-flex items-center gap-1.5 text-orange-600 hover:text-orange-700">
                        <Download className="w-4 h-4" /> Download the export as a template
                    </a>
                </div>
            </div>
            <input type="file" accept=".csv,text/csv" onChange={(e) => setData('file', e.target.files[0])}
                className={`${input} w-full file:mr-3 file:rounded file:border-0 file:bg-orange-50 file:px-3 file:py-1.5 file:text-orange-700`} />
            {errors.file && <p className="text-sm text-red-600">{errors.file}</p>}
            <button type="submit" disabled={processing || !data.file}
                className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                <Upload className="w-4 h-4" /> Preview
            </button>
        </form>
    );
}

function MapStep({ entity, label, fields, preview, input }) {
    const initial = {};
    fields.forEach((f) => { initial[f.key] = preview.guess[f.key] ?? ''; });
    const { data, setData, post, processing, errors } = useForm({ path: preview.path, map: initial });
    const [showAll, setShowAll] = useState(false);

    const setMap = (key, value) => setData('map', { ...data.map, [key]: value === '' ? '' : Number(value) });
    const rows = showAll ? preview.rows : preview.rows.slice(0, 5);

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-slate-100 bg-white p-6 space-y-4">
                <h2 className="text-sm font-semibold text-slate-700">Match your columns</h2>
                {errors.map && <p className="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">{errors.map}</p>}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {fields.map((f) => (
                        <label key={f.key} className="text-sm">
                            <span className="block text-slate-600 mb-1">{f.label}{f.required && <span className="text-red-500"> *</span>}</span>
                            <select value={data.map[f.key]} onChange={(e) => setMap(f.key, e.target.value)} className={`${input} w-full`}>
                                <option value="">— not in file —</option>
                                {preview.header.map((h, i) => <option key={i} value={i}>{h || `Column ${i + 1}`}</option>)}
                            </select>
                        </label>
                    ))}
                </div>
            </div>

            <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                <div className="px-4 py-2 border-b border-slate-100 text-xs font-medium text-slate-500">
                    Preview ({preview.rows.length} sample row{preview.rows.length === 1 ? '' : 's'})
                </div>
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead><tr className="text-left text-slate-400 border-b border-slate-100">
                            {preview.header.map((h, i) => <th key={i} className="px-3 py-2 whitespace-nowrap">{h || `Column ${i + 1}`}</th>)}
                        </tr></thead>
                        <tbody className="divide-y divide-slate-50">
                            {rows.map((r, i) => (
                                <tr key={i}>{preview.header.map((_, c) => <td key={c} className="px-3 py-1.5 whitespace-nowrap text-slate-600">{r[c]}</td>)}</tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {preview.rows.length > 5 && (
                    <button onClick={() => setShowAll((s) => !s)} className="px-4 py-2 text-xs text-orange-600 hover:text-orange-700">
                        {showAll ? 'Show less' : `Show all ${preview.rows.length}`}
                    </button>
                )}
            </div>

            <div className="flex items-center gap-3">
                <Link href={route('imports.show', entity)} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Start over</Link>
                <button onClick={() => post(route('imports.store', entity))} disabled={processing}
                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                    {processing ? 'Importing…' : `Import ${label}`}
                </button>
            </div>
        </div>
    );
}
