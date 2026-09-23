import { Head, useForm } from '@inertiajs/react';
import { FolderTree, Plus } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/inventory';
import FormField from '@/Components/FormField';
import StatusBadge from '@/Components/StatusBadge';

export default function CategoriesIndex({ categories }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '', code: '', parent_id: '',
    });

    const roots = categories.filter((c) => !c.parent_id);
    const childrenOf = (id) => categories.filter((c) => c.parent_id === id);
    const input = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';

    function add(e) {
        e.preventDefault();
        post(route('inventory.categories.store'), { preserveScroll: true, onSuccess: () => reset() });
    }

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Part Categories"
            breadcrumbs={[
                { label: 'Inventory', href: route('modules.show', 'inventory') },
                { label: 'Categories' },
            ]}
        >
            <Head title="Part Categories" />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div className="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                        <FolderTree className="w-4 h-4" />
                        Category Tree ({categories.length})
                    </h2>
                    <ul className="space-y-0.5 text-sm">
                        {roots.map((root) => (
                            <li key={root.id}>
                                <div className="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-slate-50">
                                    <span className="font-semibold text-slate-800">{root.name}</span>
                                    <span className="font-mono text-xs text-slate-400">{root.code}</span>
                                    {!root.is_active && <StatusBadge status="inactive" />}
                                </div>
                                <ul className="ml-5 border-l border-slate-100 pl-3 space-y-0.5">
                                    {childrenOf(root.id).map((child) => (
                                        <li key={child.id}>
                                            <div className="flex items-center gap-2 py-1 px-2 rounded hover:bg-slate-50">
                                                <span className="text-slate-700">{child.name}</span>
                                                <span className="font-mono text-xs text-slate-300">{child.code}</span>
                                            </div>
                                            {childrenOf(child.id).length > 0 && (
                                                <ul className="ml-5 border-l border-slate-100 pl-3">
                                                    {childrenOf(child.id).map((g) => (
                                                        <li key={g.id} className="flex items-center gap-2 py-1 px-2 rounded hover:bg-slate-50">
                                                            <span className="text-slate-500">{g.name}</span>
                                                            <span className="font-mono text-xs text-slate-300">{g.code}</span>
                                                        </li>
                                                    ))}
                                                </ul>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </li>
                        ))}
                    </ul>
                </div>

                <form onSubmit={add} className="bg-white rounded-xl shadow-sm border border-slate-100 p-5 space-y-4">
                    <h2 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Add Category</h2>
                    <FormField label="Name" htmlFor="cat-name" required error={errors.name}>
                        <input id="cat-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className={input} />
                    </FormField>
                    <FormField label="Code" htmlFor="cat-code" required error={errors.code} help="Unique, e.g. BRK-PAD">
                        <input id="cat-code" value={data.code} onChange={(e) => setData('code', e.target.value.toUpperCase())} className={`${input} font-mono`} maxLength={20} />
                    </FormField>
                    <FormField label="Parent category" htmlFor="cat-parent" error={errors.parent_id} help="Blank = top level">
                        <select id="cat-parent" value={data.parent_id} onChange={(e) => setData('parent_id', e.target.value)} className={input}>
                            <option value="">— top level —</option>
                            {categories.map((c) => <option key={c.id} value={c.id}>{c.parent_id ? '  ' : ''}{c.name}</option>)}
                        </select>
                    </FormField>
                    <button
                        type="submit"
                        disabled={processing || !data.name || !data.code}
                        className="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40"
                    >
                        <Plus className="w-4 h-4" />
                        Add Category
                    </button>
                </form>
            </div>
        </ModuleLayout>
    );
}
