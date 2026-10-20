import { useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { Upload, FileSpreadsheet, ArrowRight, Download } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

export default function ImportWizard({ entity, label, blurb, listRoute, fields, preview }) {
    const input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <ModuleLayout navConfig={navConfig} title={`Upload ${label}`}
            breadcrumbs={[{ label: 'System Administration', href: route('modules.show', 'system-admin') }, { label: 'Data Upload', href: route('imports.index') }, { label }]}>
            <Head title={`Upload ${label}`} />
            <div className="max-w-4xl space-y-6">
                <ol className="flex items-center gap-2 text-xs font-medium text-slate-400">
                    <li className={!preview ? 'text-orange-600' : ''}>1. Choose file</li>
                    <ArrowRight className="w-3 h-3" />
                    <li className={preview ? 'text-orange-600' : ''}>2. Check columns</li>
                    <ArrowRight className="w-3 h-3" />
                    <li>3. Done</li>
                </ol>

                {!preview
                    ? <UploadStep entity={entity} label={label} blurb={blurb} fields={fields} />
                    : <MapStep entity={entity} label={label} fields={fields} preview={preview} input={input} />}
            </div>
        </ModuleLayout>
    );
}

function UploadStep({ entity, label, blurb, fields }) {
    const { data, setData, post, processing, errors } = useForm({ file: null });
    const [drag, setDrag] = useState(false);
    const pick = useRef(null);

    const choose = (file) => { if (file) setData('file', file); };

    return (
        <div className="space-y-4">
            <div className="rounded-xl border border-slate-100 bg-white p-6">
                <div className="flex items-start gap-3">
                    <FileSpreadsheet className="w-6 h-6 text-orange-500 mt-0.5 shrink-0" />
                    <div className="text-sm text-slate-600">
                        <p className="font-medium text-slate-800">{label}</p>
                        <p className="mt-1">{blurb}</p>
                        <p className="mt-2 text-slate-500">
                            Columns: {fields.map((f) => f.label.replace(/ \(.*\)/, '') + (f.required ? '*' : '')).join(' · ')} <span className="text-slate-400">(* required)</span>
                        </p>
                        <a href={route('imports.template', entity)} className="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-orange-200 bg-orange-50 px-3 py-1.5 font-medium text-orange-700 hover:bg-orange-100">
                            <Download className="w-4 h-4" /> Download the template (with an example row)
                        </a>
                    </div>
                </div>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); post(route('imports.preview', entity), { forceFormData: true }); }}>
                <div
                    onDragOver={(e) => { e.preventDefault(); setDrag(true); }}
                    onDragLeave={() => setDrag(false)}
                    onDrop={(e) => { e.preventDefault(); setDrag(false); choose(e.dataTransfer.files?.[0]); }}
                    onClick={() => pick.current?.click()}
                    className={`cursor-pointer rounded-xl border-2 border-dashed p-10 text-center transition ${drag ? 'border-orange-500 bg-orange-50' : 'border-slate-300 bg-white hover:border-orange-400'}`}
                >
                    <Upload className="mx-auto h-8 w-8 text-orange-500" />
                    {data.file ? (
                        <p className="mt-3 text-sm font-medium text-slate-800">{data.file.name}</p>
                    ) : (
                        <>
                            <p className="mt-3 text-sm font-medium text-slate-800">Drop your Excel or CSV file here, or click to choose</p>
                            <p className="mt-1 text-xs text-slate-500">.xlsx, .xls or .csv · up to 20 MB · first sheet is used</p>
                        </>
                    )}
                    <input ref={pick} type="file" accept=".csv,.xlsx,.xls,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        className="hidden" onChange={(e) => choose(e.target.files?.[0])} />
                </div>
                {errors.file && <p className="mt-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">{errors.file}</p>}
                <div className="mt-4 flex items-center gap-3">
                    <button type="submit" disabled={processing || !data.file}
                        className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40">
                        {processing ? 'Reading file…' : <>Next: check columns <ArrowRight className="w-4 h-4" /></>}
                    </button>
                    <Link href={route('imports.index')} className="text-sm text-slate-500 hover:text-slate-700">Cancel</Link>
                </div>
            </form>
        </div>
    );
}

function MapStep({ entity, label, fields, preview, input }) {
    const initial = {};
    fields.forEach((f) => { initial[f.key] = preview.guess[f.key] ?? ''; });
    const { data, setData, post, processing, errors } = useForm({ path: preview.path, original_name: preview.original_name, map: initial });
    const [showAll, setShowAll] = useState(false);

    const setMap = (key, value) => setData('map', { ...data.map, [key]: value === '' ? '' : Number(value) });
    const rows = showAll ? preview.rows : preview.rows.slice(0, 5);
    const unmatchedRequired = fields.filter((f) => f.required && data.map[f.key] === '');

    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-slate-100 bg-white p-6 space-y-4">
                <div>
                    <h2 className="text-sm font-semibold text-slate-700">We matched your columns — check them</h2>
                    <p className="mt-1 text-sm text-slate-500">Each box shows which column of <strong>{preview.original_name}</strong> will fill that field. Fix any that are wrong; leave "not in file" for things you don't have.</p>
                </div>
                {errors.map && <p className="rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">{errors.map}</p>}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {fields.map((f) => (
                        <label key={f.key} className="text-sm">
                            <span className="block text-slate-600 mb-1">{f.label}{f.required && <span className="text-red-500"> *</span>}</span>
                            <select value={data.map[f.key]} onChange={(e) => setMap(f.key, e.target.value)}
                                className={`${input} w-full ${f.required && data.map[f.key] === '' ? 'border-red-400 bg-red-50' : ''}`}>
                                <option value="">— not in file —</option>
                                {preview.header.map((h, i) => <option key={i} value={i}>{h || `Column ${i + 1}`}</option>)}
                            </select>
                        </label>
                    ))}
                </div>
            </div>

            <div className="rounded-xl border border-slate-100 bg-white overflow-hidden">
                <div className="px-4 py-2 border-b border-slate-100 text-xs font-medium text-slate-500">
                    What we read from your file ({preview.rows.length} sample row{preview.rows.length === 1 ? '' : 's'})
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
                <button onClick={() => post(route('imports.store', entity))} disabled={processing || unmatchedRequired.length > 0}
                    title={unmatchedRequired.length ? `Match: ${unmatchedRequired.map((f) => f.label).join(', ')}` : undefined}
                    className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed">
                    {processing ? 'Uploading…' : <><Upload className="w-4 h-4" /> Upload {label}</>}
                </button>
                <Link href={route('imports.show', entity)} className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">Choose a different file</Link>
            </div>
        </div>
    );
}
