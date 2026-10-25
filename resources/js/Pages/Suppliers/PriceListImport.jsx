import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { FileDown, FileUp, Upload } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/suppliers';
import FormField from '@/Components/FormField';

/**
 * The price-list import wizard (Module 6.2):
 * Step 0 pick a part list & download a pre-filled template →
 * Step 1 upload CSV/xlsx → Step 2 map columns over a match preview → import.
 */
export default function PriceListImport({ supplier, supplierPartCount = 0, activeList = null, categories = [], preview = null }) {
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
                {preview === null
                    ? <UploadStep supplier={supplier} supplierPartCount={supplierPartCount} activeList={activeList} categories={categories} />
                    : <MapStep supplier={supplier} preview={preview} />}
            </div>
        </ModuleLayout>
    );
}

function UploadStep({ supplier, supplierPartCount, activeList, categories }) {
    const [file, setFile] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [scope, setScope] = useState('supplier_parts');
    const [categoryId, setCategoryId] = useState('');

    function upload(e) {
        e.preventDefault();
        if (!file) return;
        setUploading(true);
        router.post(route('suppliers.pricelists.preview', supplier.id), { file }, {
            forceFormData: true,
            onFinish: () => setUploading(false),
        });
    }

    const templateUrl = route('suppliers.pricelists.template', supplier.id)
        + `?scope=${scope}${scope === 'category' && categoryId ? `&category_id=${categoryId}` : ''}`;
    const select = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    return (
        <div className="space-y-6">
            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-8">
                <h1 className="text-xl font-bold text-slate-900 mb-1">Step 0 — Get a pre-filled template</h1>
                <p className="text-sm text-slate-500 mb-5">
                    No file yet? Download a workbook already filled with the part numbers and descriptions you choose —
                    you only type the <strong>New Cost</strong> column and upload it back.
                    {activeList && <> Current costs come from the active list <strong>{activeList.name}</strong>.</>}
                </p>
                <div className="flex flex-wrap items-end gap-3">
                    <div className="w-64">
                        <label className="text-xs font-semibold uppercase tracking-wider text-slate-400">Price these items</label>
                        <select value={scope} onChange={(e) => setScope(e.target.value)} className={`${select} mt-1`}>
                            <option value="supplier_parts">Parts this supplier carries ({supplierPartCount.toLocaleString()})</option>
                            <option value="category">One category…</option>
                            <option value="all">All active parts</option>
                        </select>
                    </div>
                    {scope === 'category' && (
                        <div className="w-64">
                            <label className="text-xs font-semibold uppercase tracking-wider text-slate-400">Category</label>
                            <select value={categoryId} onChange={(e) => setCategoryId(e.target.value)} className={`${select} mt-1`}>
                                <option value="">— pick —</option>
                                {categories.map((c) => <option key={c.id} value={c.id}>{c.label}</option>)}
                            </select>
                        </div>
                    )}
                    <a href={templateUrl}
                        className="inline-flex items-center gap-2 rounded-lg border border-orange-600 px-4 py-2 text-sm font-semibold text-orange-700 hover:bg-orange-50">
                        <FileDown className="w-4 h-4" /> Download .xlsx template
                    </a>
                </div>
            </div>

            <form onSubmit={upload} className="bg-white rounded-xl shadow-sm border border-slate-100 p-8">
                <h1 className="text-xl font-bold text-slate-900 mb-1">Step 1 — Upload the supplier's file</h1>
                <p className="text-sm text-slate-500 mb-6">
                    CSV or Excel (.xlsx/.xls), first row must be column headers. You map their columns to ours before
                    anything imports — the preview shows which rows already match our parts.
                </p>
                <label className="flex flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed border-slate-300 py-12 cursor-pointer hover:border-orange-400 hover:bg-orange-50/40 transition-colors">
                    <FileUp className="w-8 h-8 text-slate-400" />
                    <span className="text-sm text-slate-600">
                        {file ? <strong>{file.name}</strong> : 'Click to choose a .csv or .xlsx file'}
                    </span>
                    <input type="file" accept=".csv,.txt,.xlsx,.xls" className="hidden" onChange={(e) => setFile(e.target.files[0] ?? null)} />
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
        </div>
    );
}

function MapStep({ supplier, preview }) {
    const { data, setData, post, processing, errors } = useForm({
        path: preview.path,
        name: `${supplier.name} — ${new Date().toISOString().slice(0, 10)}`,
        effective_date: new Date().toISOString().slice(0, 10),
        notes: '',
        map: {
            supplier_part_number: preview.guess.supplier_part_number ?? '',
            cost_price: preview.guess.cost_price ?? '',
            description: preview.guess.description ?? '',
            minimum_qty: preview.guess.minimum_qty ?? '',
        },
        activate: true,
    });

    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
    const columnOptions = preview.header.map((h, i) => ({ value: i, label: `${i + 1}: ${h}` }));
    const matches = preview.matches ?? {};
    const numCol = data.map.supplier_part_number;
    const matchCount = numCol === '' ? 0 : preview.rows.filter((_, r) => matches[`${r}.${numCol}`]).length;

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
                <p className="text-sm text-slate-500 mb-5">
                    Best guesses are pre-selected — check them against the preview below.
                    {numCol !== '' && (
                        <span className={`ml-2 rounded-full px-2 py-0.5 text-xs font-semibold ${matchCount === preview.rows.length ? 'bg-emerald-100 text-emerald-700' : matchCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}`}>
                            {matchCount}/{preview.rows.length} rows match a part
                        </span>
                    )}
                </p>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <FormField label="List name" htmlFor="pl-name" required error={errors.name}>
                        <input id="pl-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Effective date" htmlFor="pl-eff" error={errors.effective_date}>
                        <input id="pl-eff" type="date" value={data.effective_date} onChange={(e) => setData('effective_date', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Notes" htmlFor="pl-notes" error={errors.notes}>
                        <input id="pl-notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} placeholder="e.g. October price update" className={input} />
                    </FormField>
                </div>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
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
                    <FormField label="Min. qty column" htmlFor="pl-map-min" error={errors['map.minimum_qty']}>
                        <select id="pl-map-min" value={data.map.minimum_qty} onChange={(e) => setMap('minimum_qty', e.target.value)} className={input}>
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
                            {numCol !== '' && <th className="px-3 py-2">Match</th>}
                            {preview.header.map((h, i) => (
                                <th key={i} className={`px-3 py-2 first:pl-6 ${
                                    [numCol, data.map.cost_price].includes(i) ? 'bg-orange-50 text-orange-700' : ''
                                }`}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50">
                        {preview.rows.map((row, r) => {
                            const hit = numCol !== '' ? matches[`${r}.${numCol}`] : null;
                            return (
                                <tr key={r}>
                                    {numCol !== '' && (
                                        <td className="px-3 py-1.5 first:pl-6">
                                            {hit
                                                ? <span className="inline-flex items-center gap-1 text-emerald-700" title={hit.description}>✓ <span className="font-mono">{hit.part_number}</span></span>
                                                : <span className="text-red-500" title="No part match — row still imports as unmatched">✗</span>}
                                        </td>
                                    )}
                                    {row.map((cell, c) => (
                                        <td key={c} className={`px-3 py-1.5 text-slate-600 ${
                                            [numCol, data.map.cost_price].includes(c) ? 'bg-orange-50/50 font-medium' : ''
                                        }`}>{cell}</td>
                                    ))}
                                </tr>
                            );
                        })}
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
