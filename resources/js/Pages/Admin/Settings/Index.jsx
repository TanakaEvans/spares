import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { ArrowLeft, Globe, MapPin, RotateCcw, Save, Search, ShieldAlert } from 'lucide-react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/system-admin';

function SettingInput({ setting, value, onChange, disabled }) {
    const base =
        'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500 disabled:bg-slate-50 disabled:text-slate-400';

    if (setting.type === 'bool') {
        return (
            <button
                type="button"
                disabled={disabled}
                onClick={() => onChange(!value)}
                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                    value ? 'bg-orange-600' : 'bg-slate-300'
                } ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
                aria-pressed={value}
            >
                <span
                    className={`inline-block h-4 w-4 rounded-full bg-white transition-transform ${
                        value ? 'translate-x-6' : 'translate-x-1'
                    }`}
                />
            </button>
        );
    }

    if (setting.type === 'select') {
        return (
            <select value={value ?? ''} onChange={(e) => onChange(e.target.value)} disabled={disabled} className={base}>
                {Object.entries(setting.options ?? {}).map(([optValue, optLabel]) => (
                    <option key={optValue} value={optValue}>
                        {optLabel}
                    </option>
                ))}
            </select>
        );
    }

    if (['int', 'decimal', 'percent', 'money'].includes(setting.type)) {
        return (
            <div className="relative">
                <input
                    type="number"
                    step={setting.type === 'int' ? 1 : 0.01}
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value)}
                    disabled={disabled}
                    className={`${base} text-right tabular-nums ${setting.type === 'percent' ? 'pr-8' : ''}`}
                />
                {setting.type === 'percent' && (
                    <span className="absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                )}
            </div>
        );
    }

    return (
        <input
            type="text"
            value={value ?? ''}
            onChange={(e) => onChange(e.target.value)}
            disabled={disabled}
            className={base}
        />
    );
}

export default function SettingsIndex({ auth, settings, branches, editingBranchId }) {
    const { errors } = usePage().props;
    const [search, setSearch] = useState('');
    const [dirty, setDirty] = useState({});
    const { processing } = useForm();

    const isBranchView = editingBranchId != null;

    const groups = useMemo(() => {
        const bySearch = settings.filter(
            (s) =>
                !search ||
                s.label.toLowerCase().includes(search.toLowerCase()) ||
                s.key.toLowerCase().includes(search.toLowerCase())
        );
        const map = new Map();
        for (const s of bySearch) {
            if (!map.has(s.group)) map.set(s.group, []);
            map.get(s.group).push(s);
        }
        return [...map.entries()];
    }, [settings, search]);

    const [activeGroup, setActiveGroup] = useState(null);
    const visibleGroups = activeGroup && !search ? groups.filter(([g]) => g === activeGroup) : groups;

    function switchBranch(branchId) {
        setDirty({});
        router.get(route('admin.settings.index'), branchId ? { branch: branchId } : {}, {
            preserveState: false,
        });
    }

    function setValue(key, value) {
        setDirty((d) => ({ ...d, [key]: value }));
    }

    function save() {
        router.put(
            route('admin.settings.update'),
            { branch_id: editingBranchId, values: dirty },
            { preserveScroll: true, onSuccess: () => setDirty({}) }
        );
    }

    function revert(key) {
        router.delete(route('admin.settings.revert'), {
            data: { branch_id: editingBranchId, key },
            preserveScroll: true,
        });
    }

    const dirtyCount = Object.keys(dirty).length;

    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Configuration Centre"
            breadcrumbs={[
                { label: 'System Administration', href: route('modules.show', 'system-admin') },
                { label: 'Configuration Centre' },
            ]}
        >
            <Head title="Configuration Centre" />

            <div className="space-y-6">
                <div className="bg-white rounded-xl shadow-sm p-6 border border-slate-100">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Configuration Centre</h1>
                            <p className="text-slate-500 mt-1 text-sm">
                                Every configurable business value in one place — nothing is hardcoded.
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <select
                                value={editingBranchId ?? ''}
                                onChange={(e) => switchBranch(e.target.value || null)}
                                className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium"
                            >
                                <option value="">🌐 Global (all branches)</option>
                                {branches.map((b) => (
                                    <option key={b.id} value={b.id}>
                                        📍 {b.name}
                                    </option>
                                ))}
                            </select>
                            <button
                                onClick={save}
                                disabled={dirtyCount === 0 || processing}
                                title={dirtyCount === 0 ? 'No changes to save' : undefined}
                                className="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            >
                                <Save className="w-4 h-4" />
                                Save{dirtyCount > 0 ? ` (${dirtyCount})` : ''}
                            </button>
                        </div>
                    </div>
                    {isBranchView && (
                        <div className="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-2.5 text-sm text-amber-800">
                            Editing branch overrides. Settings without an override inherit the global value; only
                            per-branch settings can be changed here.
                        </div>
                    )}
                </div>

                <div className="flex gap-6">
                    {/* Group rail */}
                    <aside className="hidden lg:block w-56 shrink-0">
                        <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-3 sticky top-6">
                            <div className="relative mb-3">
                                <Search className="absolute left-2.5 top-2.5 w-4 h-4 text-slate-400" />
                                <input
                                    type="text"
                                    placeholder="Search settings…"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full rounded-lg border border-slate-200 pl-8 pr-2 py-1.5 text-sm"
                                />
                            </div>
                            <button
                                onClick={() => setActiveGroup(null)}
                                className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                                    !activeGroup ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-slate-600 hover:bg-slate-50'
                                }`}
                            >
                                All groups
                            </button>
                            {groups.map(([group, items]) => (
                                <button
                                    key={group}
                                    onClick={() => setActiveGroup(group)}
                                    className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex justify-between ${
                                        activeGroup === group
                                            ? 'bg-orange-50 text-orange-700 font-semibold'
                                            : 'text-slate-600 hover:bg-slate-50'
                                    }`}
                                >
                                    {group}
                                    <span className="text-slate-300">{items.length}</span>
                                </button>
                            ))}
                        </div>
                    </aside>

                    {/* Settings groups */}
                    <div className="flex-1 space-y-6 min-w-0">
                        {visibleGroups.map(([group, items]) => (
                            <div key={group} className="bg-white rounded-xl shadow-sm border border-slate-100">
                                <h2 className="px-6 pt-5 pb-3 text-sm font-semibold uppercase tracking-wider text-slate-400 border-b border-slate-100">
                                    {group}
                                </h2>
                                <div className="divide-y divide-slate-50">
                                    {items.map((setting) => {
                                        const branchLocked = isBranchView && !setting.per_branch;
                                        const currentValue =
                                            setting.key in dirty ? dirty[setting.key] : setting.value;
                                        return (
                                            <div key={setting.key} className="px-6 py-4 flex flex-wrap items-start gap-4">
                                                <div className="flex-1 min-w-[240px]">
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-sm font-medium text-slate-800">
                                                            {setting.label}
                                                        </span>
                                                        {setting.sensitive && (
                                                            <ShieldAlert className="w-3.5 h-3.5 text-amber-500" title="Sensitive setting" />
                                                        )}
                                                        {isBranchView && setting.per_branch && (
                                                            <span
                                                                className={`text-[10px] font-semibold px-1.5 py-0.5 rounded-full ${
                                                                    setting.is_overridden
                                                                        ? 'bg-orange-100 text-orange-700'
                                                                        : 'bg-slate-100 text-slate-500'
                                                                }`}
                                                            >
                                                                {setting.is_overridden ? 'Overridden' : 'Inherits global'}
                                                            </span>
                                                        )}
                                                    </div>
                                                    {setting.help && (
                                                        <p className="text-xs text-slate-400 mt-0.5">{setting.help}</p>
                                                    )}
                                                    {errors?.[`values.${setting.key}`] && (
                                                        <p className="text-xs text-red-600 mt-1">
                                                            {errors[`values.${setting.key}`]}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="w-64 flex items-center gap-2">
                                                    <div className="flex-1">
                                                        <SettingInput
                                                            setting={setting}
                                                            value={currentValue}
                                                            onChange={(v) => setValue(setting.key, v)}
                                                            disabled={branchLocked}
                                                        />
                                                    </div>
                                                    {isBranchView && setting.is_overridden && (
                                                        <button
                                                            onClick={() => revert(setting.key)}
                                                            title="Revert to global value"
                                                            className="p-2 rounded-lg text-slate-400 hover:text-orange-600 hover:bg-orange-50 transition-colors"
                                                        >
                                                            <RotateCcw className="w-4 h-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                        {visibleGroups.length === 0 && (
                            <div className="bg-white rounded-xl shadow-sm border border-slate-100 p-12 text-center text-slate-400">
                                No settings match "{search}".
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
