import { Head, router } from '@inertiajs/react';
import ModuleLayout from '@/Layouts/ModuleLayout';
import navConfig from '@/nav/reports';
import { StatTile, LineChart, LabelledBars, HBarList } from '@/Components/Charts';

export default function Dashboard({ kpis, salesTrend, arAgeing, topParts, salesByCategory, branches, filters }) {
    return (
        <ModuleLayout
            navConfig={navConfig}
            title="Executive Dashboard"
            breadcrumbs={[{ label: 'Reports & Analytics', href: route('modules.show', 'reports') }, { label: 'Executive Dashboard' }]}
        >
            <Head title="Executive Dashboard" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <p className="text-sm text-slate-500">Live figures — refreshed from the ledger on every load.</p>
                    <select aria-label="Branch" value={filters.branch_id ?? ''}
                        onChange={(e) => router.get(route('reports.dashboard'), e.target.value ? { branch_id: e.target.value } : {}, { preserveState: true, replace: true })}
                        className="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All branches</option>
                        {branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}
                    </select>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
                    <StatTile label="Today's sales" value={kpis.today_sales} money tone="emerald" />
                    <StatTile label="Month to date" value={kpis.mtd_sales} money />
                    <StatTile label="Outstanding AR" value={kpis.outstanding_ar} money tone="blue" />
                    <StatTile label="Overdue 60+" value={kpis.overdue_ar_60} money tone={kpis.overdue_ar_60 > 0 ? 'red' : 'slate'} />
                    <StatTile label="Stock value (cost)" value={kpis.stock_value} money />
                    <StatTile label="Open jobs" value={kpis.open_jobs} tone="orange" />
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div className="lg:col-span-2 rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Sales trend — this year vs last</h2>
                        <LineChart
                            data={salesTrend}
                            series={[
                                { key: 'this_year', label: 'This year', color: '#ea580c' },
                                { key: 'last_year', label: 'Last year', color: '#94a3b8' },
                            ]}
                        />
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Debtors ageing</h2>
                        <LabelledBars data={arAgeing.map((a) => ({ label: a.bucket, value: a.value }))} />
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Top parts by value (MTD)</h2>
                        <HBarList data={topParts.map((p) => ({ label: p.part_number, value: p.revenue }))} labelKey="label" valueKey="value" />
                    </div>
                    <div className="rounded-xl border border-slate-100 bg-white p-5">
                        <h2 className="text-sm font-semibold text-slate-700 mb-3">Sales by category (MTD)</h2>
                        <HBarList data={salesByCategory.map((c) => ({ label: c.category, value: c.revenue }))} labelKey="label" valueKey="value" />
                    </div>
                </div>
            </div>
        </ModuleLayout>
    );
}
