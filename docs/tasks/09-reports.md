# Tasks: Reports & Analytics

> Phase 6 of the [implementation plan](../implementation-plan.md). Spec: [module doc](../modules/09-reports-analytics.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

Architecture (applies to every report): Inertia controller action, server-side pagination for large sets, Excel export (`maatwebsite/laravel-excel`), PDF export (DomPDF), date range + branch filter, branch-level access control. Charts: Recharts with server-fetched props.

> **Phase 6 core delivered (2026-09-23).** The reporting surface is live on real cross-phase data; browser-verified: executive dashboard (KPIs + charts) and inventory report render off the ledger. All figures reconcile to the sub-ledgers via `ReportServiceTest` (5).
> - **9.1 Executive Dashboard** ✅ — `ReportService::kpis` (today/MTD net sales, outstanding + overdue AR, stock value at cost, open jobs), `salesTrend` (this vs last year), `arAgeingProfile`, `topPartsByValue`, `salesByCategory`; `Reports/Dashboard.jsx` with a lightweight inline-SVG chart kit (`Components/Charts.jsx` — StatTile/LineChart/Bars/HBarList, **no external dependency**), branch filter, live-on-load. Deferred: sales budget/target config + "MTD vs target" tile, branch-manager permission scoping.
> - **9.2 Sales Reports** ✅ — `Reports/Sales/Index.jsx`: summary (count/gross/VAT/ATV, by payment method), sales by category, top parts, top customers, date+branch filter. Deferred: quote-conversion, back-order, returns-analysis, salesperson breakdown, Excel/PDF export.
> - **9.3 Inventory Reports** ✅ — `Reports/Inventory/Index.jsx`: **stock value reconciled to the 1310 control account** (the check correctly flags demo opening stock that was never journalised), stock-on-hand with below-reorder/out/negative filters, stock ageing by value. Deferred: ABC analysis, dead-stock report, movement ledger per part.
> - **9.5 Customer Reports** ✅ — `Reports/Customers/Index.jsx`: debtors ageing (reuses Phase 4 calc), top customers, dormant (configurable 60/90/180), credit review (≥80% of limit / on hold). Deferred: purchase-history drill, new-customer report.
> - **9.6 Supplier Reports** ✅ — `Reports/Suppliers/Index.jsx`: spend by supplier, aged creditors (reuses Phase 4 calc), open POs with overdue flag. Deferred: price-list comparison.
> - **9.7 Workshop Reports** ✅ — `Reports/Workshop/Index.jsx`: productivity (opened/completed/invoiced/revenue/avg), job profitability (cost/billed/margin per job). Deferred: technician efficiency (needs clock-on/off), comeback rate.
> - Reports module card + sub-cards Active; `nav/reports.js`; 2 page guides. Tests: `ReportServiceTest` (5). **Suite 165 green.**
> - **Deferred to a Phase 6 follow-up:** 9.4 extra financial reports (cash flow, gross-margin-by-category, sales-vs-budget), 9.8 Scheduled reports (needs `scheduled_reports` + scheduler + email), Excel/PDF export pipeline, ABC/dead-stock, and the cross-phase deferred features (bank rec, VAT-returns UI, warranty claims, promotions/lay-by/loyalty, requisitions, import shipments, serial/batch, supplier performance).

## 9.1 Executive Dashboard  ·  [spec](../modules/09-reports-analytics/9.1-executive-dashboard.md)

### Backend
- [ ] KPI queries (real-time, no pre-aggregated caches): today/yesterday/MTD sales, MTD vs target, outstanding AR, overdue AR 60+, stock value at cost, open job cards
- [ ] Chart data endpoints: 12-month sales trend (this vs last year), top 10 parts by value, sales by category, AR ageing profile, branch comparison
- [ ] Sales budget/target configuration (required by "MTD vs Target" tile)
- [ ] Branch permission scoping (branch manager sees own branch only)
- [ ] Route (`reports.dashboard`) + SystemRoute permission seeds
### Frontend
- [ ] Executive dashboard (`reports.dashboard` → `Reports/Dashboard.jsx`) — KPI tile row + Recharts charts, refresh on focus (Inertia partial reload)
- [ ] Sidebar nav entry in reports module nav config
### Tests
- [ ] Feature tests: KPI figures match seeded transactions; branch manager response excludes other branches
- [ ] Functional pass: post a sale, refresh dashboard, watch Today's Sales and Stock Value move

## 9.2 Sales Reports  ·  [spec](../modules/09-reports-analytics/9.2-sales-reports.md)

### Backend + Frontend (`reports.sales.index` → `Reports/Sales/Index.jsx`)
- [ ] Daily sales summary (by payment method, transaction count, ATV, top 5 parts, cashier breakdown, VAT collected)
- [ ] Sales by period (daily→yearly; filters: branch, salesperson, customer, group, category; margin columns)
- [ ] Sales by part (incl. on-hand, last sale date, sell-through)
- [ ] Sales by category (with subcategory drill-down)
- [ ] Sales by customer / by salesperson
- [ ] Quotation conversion report (win rate, lost reasons)
- [ ] Back-order report; returns analysis (credit notes by reason/salesperson/part)
### Tests
- [ ] Feature tests: daily summary totals reconcile to posted invoices minus credit notes; margin % uses AVCO cost
- [ ] Functional pass: run daily summary for a seeded trading day and tie it to the GL sales figure

## 9.3 Inventory Reports  ·  [spec](../modules/09-reports-analytics/9.3-inventory-reports.md)

### Backend + Frontend (`reports.inventory.index` → `Reports/Inventory/Index.jsx`)
- [ ] Stock on hand (on hand / reserved / available; below-reorder, out-of-stock, negative flags)
- [ ] Reorder report with one-click purchase requisition creation
- [ ] Stock ageing (0–30 / 31–60 / 61–90 / 91–180 / 180+ brackets, value per bracket) + dead stock report (12-month zero movement)
- [ ] ABC analysis (A top 20% / B next 30% / C bottom 50% by sales value)
- [ ] Stock movement report (full ledger per part/date range)
- [ ] Stock value report (reconciles to Inventory GL account) + stock take variance + inter-branch transfer report
### Tests
- [ ] Feature tests: stock value report equals Inventory control account balance; ABC class boundaries correct
- [ ] Functional pass: age some seeded stock, run ageing + dead stock, spot-check one part's movement ledger

## 9.4 Financial Reports  ·  [spec](../modules/09-reports-analytics/9.4-financial-reports.md)

TB / P&L / balance sheet built in Phase 4 — see [tasks/07-finance.md](07-finance.md). This suite adds the rest under `reports.finance.index` → `Reports/Finance/Index.jsx`.

- [ ] Cash flow statement (operating / investing / financing)
- [ ] AR ageing + AP ageing report views (reuse Phase 4 ageing calcs)
- [ ] VAT summary; bank reconciliation report view
- [ ] Gross margin by category; sales vs budget
- [ ] Feature tests: cash flow ties to bank GL movement; sales vs budget uses dashboard budget config
- [ ] Functional pass: run each report for a closed month, cross-check against trial balance

## 9.5 Customer Reports  ·  [spec](../modules/09-reports-analytics/9.5-customer-reports.md)

### Backend + Frontend (`reports.customers.index` → `Reports/Customers/Index.jsx`)
- [ ] Customer ageing (debtors) with high-risk flags
- [ ] Top customers (by spend / visits / margin; MTD, YTD, 12 months)
- [ ] Customer purchase history; dormant customers (configurable 60/90/180 days); new customer report
- [ ] Credit limit review (approaching/exceeding limit, on-hold list, recommended adjustments)
### Tests
- [ ] Feature tests: dormancy threshold honours the configured days; credit review flags over-limit accounts
- [ ] Functional pass: run top customers and confirm ranking against seeded invoices

## 9.6 Supplier Reports  ·  [spec](../modules/09-reports-analytics/9.6-supplier-reports.md)

### Backend + Frontend (`reports.suppliers.index` → `Reports/Suppliers/Index.jsx`)
- [ ] Supplier spend report (period totals per supplier)
- [ ] Purchase order status (open POs, overdue deliveries)
- [ ] Supplier price list comparison (same part across suppliers: cost, lead time, MOQ)
### Tests
- [ ] Feature tests: spend report equals posted supplier invoices per period; overdue PO = delivery date passed and not fully received
- [ ] Functional pass: run PO status with one overdue seeded PO

## 9.7 Workshop Reports  ·  [spec](../modules/09-reports-analytics/9.7-workshop-reports.md)

### Backend + Frontend (`reports.workshop.index` → `Reports/Workshop/Index.jsx`)
- [ ] Workshop productivity (jobs opened/completed/invoiced, revenue, avg job value)
- [ ] Technician efficiency (flat-rate hrs billed vs actual; utilisation %)
- [ ] Job profitability (parts cost + labour cost vs billed, margin per job)
- [ ] Comeback rate (same fault within 30 days, by technician); outstanding job cards (open > X days)
- [ ] Parts usage in workshop; warranty claims report (claimed vs recovered, by supplier)
### Tests
- [ ] Feature tests: efficiency % matches technician KPI formula; comeback matches 30-day same-fault rule
- [ ] Functional pass: run efficiency report against seeded time logs and hand-check one technician

## 9.8 Scheduled Reports  ·  [spec](../modules/09-reports-analytics/9.8-scheduled-reports.md)

### Backend
- [ ] Migration(s): `scheduled_reports` (report_key, frequency, run_time/run_day, parameters json, recipients json, format)
- [ ] Scheduler command: generate PDF/Excel via export pipeline, email via Phase 0 email pipeline, stamp `last_run_at`
- [ ] Seed defaults: Daily Sales Summary 08:00, Weekly Stock Reorder Mon 07:00, Monthly AR/AP Ageing, Monthly P&L, Top Customers MTD, Dormant Customer List
- [ ] Form Requests + Policies; controller + routes (`reports.scheduled.*`) + SystemRoute permission seeds
### Frontend
- [ ] Scheduled reports page (`reports.scheduled.index` → `Reports/Scheduled/Index.jsx`) — schedule editor, recipients, run-now action
### Tests
- [ ] Feature tests: due-report selection by frequency/run_time/run_day; inactive schedules skipped
- [ ] Unit tests: report renderer resolves report_key + parameters
- [ ] Functional pass: schedule the daily sales summary to a test inbox, force the scheduler, receive the PDF

## Deferred (needs other deferred features first)
- [ ] Loyalty points report — after 5.6 loyalty programme
- [ ] Supplier performance scorecard — after 6.4 supplier performance
- [ ] Landed cost analysis — after 3.7 import shipments
