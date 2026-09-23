# AGENTS.md — SparesPro ERP

> Operating manual for AI agents (and humans) working in this repo. Read this first; it tells you which docs to read next, and the non-negotiable rules. **The docs are the spec — never invent behaviour that contradicts them; never write code for a feature before reading its sub-module doc.**

---

## What this project is

Motor spares (auto parts) ERP for Southern Africa (SA/Zimbabwe + region). **Laravel 12 + Inertia.js 2 + React 18 + Tailwind v4**, nwidart/laravel-modules architecture, MySQL. Multi-branch. Local-first: no paid external integrations, no runtime internet dependency for trading.

---

## Doc map — read in this order for any task

| You are about to… | Read (in order) |
|--------------------|-----------------|
| Do anything at all | This file → [docs/README.md](docs/README.md) (index) |
| Start a work session | [docs/implementation-plan.md](docs/implementation-plan.md) → the current phase's tasks file in [docs/tasks/](docs/tasks/README.md) |
| Build a feature | Its module overview `docs/modules/NN-*.md` → its sub-module spec `docs/modules/NN-*/N.n-*.md` (fields, rules, edge cases, screens) → the cross-module workflow it belongs to in [docs/workflows/](docs/workflows/README.md) |
| Write backend code | [docs/coding-standards/php-laravel.md](docs/coding-standards/php-laravel.md) + [database.md](docs/coding-standards/database.md) |
| Write frontend code | [docs/coding-standards/react-inertia.md](docs/coding-standards/react-inertia.md) + [docs/design/ui-rules.md](docs/design/ui-rules.md) + [component-standards.md](docs/design/component-standards.md) |
| Debug / handle errors | [docs/coding-standards/debugging-standards.md](docs/coding-standards/debugging-standards.md) |
| Unsure what a term means | [docs/glossary.md](docs/glossary.md) |
| Build a screen/menu | [docs/design/navigation-and-layout.md](docs/design/navigation-and-layout.md) + [module-menus.md](docs/design/module-menus.md) + [screen-designs.md](docs/design/screen-designs.md) |
| Touch settings/config | [docs/configuration-centre.md](docs/configuration-centre.md) |
| Touch seed/reference data | [docs/integrations/local-data-seeding.md](docs/integrations/local-data-seeding.md) |
| Write tests / finish a task | [docs/testing-strategy.md](docs/testing-strategy.md) |
| Currency, printing, backups, migration | [docs/operations/](docs/operations/) |

Don't bulk-read all 100+ docs — follow the table. Sub-module specs are self-contained (~150 lines each).

---

## The task workflow (mandatory)

```
implementation-plan.md  →  docs/tasks/NN-*.md  →  sub-module spec  →  code  →  tests  →  functional pass  →  tick the box
```

1. Work in the order of the current phase in [docs/implementation-plan.md](docs/implementation-plan.md). Don't jump phases without being asked.
2. Before starting a task: set it `[~]` in its tasks file.
3. A task is `[x]` ONLY when: code written per spec, automated tests pass, functional pass done ([testing-strategy.md](docs/testing-strategy.md)). Annotate: `[x] … — done YYYY-MM-DD`.
4. **Update the tasks file in the same commit as the work.** If reality diverged from the spec, update the sub-module doc too — docs must never lie.
5. New follow-up work discovered? Add it as a `[ ]` task in the right file — not a TODO comment in code.

---

## Non-negotiable rules

### Architecture
1. Business logic lives in **Services**; controllers are thin HTTP (validate → authorize → service → respond). Form Requests + Policies on every action.
2. **All stock movements go through `StockLedgerService`. All GL postings go through `GlPostingService`.** No direct writes to `stock_ledger`, `stock_levels`, `gl_journals` anywhere else. Both wrap `DB::transaction()` with `lockForUpdate()`.
3. Posted documents are immutable — corrections happen via counter-documents (credit note, reversal journal, supplier return).
4. Every module is an nwidart module under `Modules/`; base `app/` is shared infrastructure only.
5. New routes get `SystemRoute` permission seeds; route name = permission key (`module.resource.action`).

### Configuration over code
6. **No hardcoded business values.** VAT rates, thresholds, prefixes, terms, rates, templates, tolerances — everything on the checklist in [configuration-centre.md](docs/configuration-centre.md) is read via `SettingsService::get(key, branch)`. A literal business value in a PR is a rejection.
7. **Data is captured once, reused everywhere.** Company/branch identity (name, logo, VAT no, banking) renders through the shared document-header partials on every invoice/receipt/statement/email — never re-typed per template.

### Multi-branch
8. Every transactional table carries `branch_id`; queries are branch-scoped by default (global scope on the user's active branch); "all branches" is explicit and permissioned. Settings, sequences, price lists and printers support per-branch overrides with global fallback.

### Money & data
9. Money = `decimal(15,2)`, quantities `decimal(10,2)`, rates `decimal(15,6)` — never floats. Base-currency posting with per-transaction currency + rate stored ([multi-currency.md](docs/operations/multi-currency.md)).
10. Soft deletes on business entities; append-only ledgers; every override/approval audited (who, when, before/after).
11. Seeders are idempotent (`updateOrCreate` on natural keys); reference data comes from the CSV seed packs.

### Frontend
12. Sidebars are three-level: module cards (dashboard) → module sidebar ([module-menus.md](docs/design/module-menus.md)) → **per-sub-module contextual sidebar** ([docs/design/sidebars/](docs/design/sidebars/README.md)) showing ONLY that sub-module's screens plus curated Quick Links. Every sub-module gets its own sidebar built from its entry there; mixing another sub-module's items into WORK/INSIGHTS/SETUP fails review. Pages are one of the four archetypes (List/Form/Detail/Operational).
13. Follow [ui-rules.md](docs/design/ui-rules.md) — keyboard-first operational screens, scan-target focus management, disabled-with-reason over reject-after-click, drafts on operational documents, partial reloads, `useForm` for all forms, `route()` (Ziggy) for all URLs, shared components (`StatusBadge`, `DataTable`, `FormField`, `MoneyDisplay`, `ConfirmDialog`, `PageGuide`, `CommandPalette`) — never one-off variants.
13a. **Every page ships with a Page Guide** ([spec](docs/design/page-guide.md)) — a collapsible `F1` help block written from the sub-module spec; behaviour changes update the guide in the same PR.
13b. **Every rendered entity reference is a hyperlink** (UI-13): part numbers, customer names, document numbers, suppliers, technicians — all Inertia `<Link>`s with state-preserving Back. Plain-text entity references fail review.
13c. **Components follow [component-standards.md](docs/design/component-standards.md) exactly**: one primary button per view, Radix dialogs only (no stacking), no raw hex colours in components, and **one sidebar theme — every module sidebar inherits the main layout's dark theme via the shared `ModuleLayout`; `nav.js` configs carry content only, never styling.**

### Errors & debugging
13d. Services throw **typed domain exceptions** with `userMessage()` + `context()`; no empty catches, no `dd()`/`console.log` in commits, structured `module.event` log keys — [debugging-standards.md](docs/coding-standards/debugging-standards.md). New invariants register as health checks ([system-health.md](docs/operations/system-health.md)).

### Process
14. Conventional commits `type(scope): description` (scope = module), branches `feature|fix/module-desc` — see [git-workflow.md](docs/coding-standards/git-workflow.md). Tests in the same PR as the feature; a production bug gets a failing test before its fix.
15. Never `git push --force`, never skip hooks, never edit posted data in the DB by hand.

---

## Repo layout cheat-sheet

```
AGENTS.md                    ← you are here
docs/                        ← the full spec (start: docs/README.md)
  implementation-plan.md     ← build order + why
  tasks/                     ← living checklists (00-foundations … 10-system-admin)
  modules/NN-*.md + NN-*/    ← module overviews + per-sub-module specs
  workflows/  design/  operations/  coding-standards/  integrations/
  configuration-centre.md  testing-strategy.md  data-sources.md  architecture.md
app/                         ← shared infra only (User, Branch, middleware, SystemModule)
Modules/                     ← one nwidart module per business domain
resources/js/Layouts|Components/ ← shared shell + UI kit
database/data/               ← CSV seed packs (planned — see local-data-seeding.md)
```

## Current state (update this section as phases complete)

- **Built (pre-existing):** System Administration core (users, roles, company, branches, departments, auth flows).
- **Built (Phase 0, 2026-09-22):**
  - Dashboard with the 10 SparesPro module cards + sub-module card pages (`resources/js/modules.js`, `Pages/Modules/Show.jsx`) — 0.7 ✅
  - 0.1 Configuration Centre ✅ — `config/settings_registry.php` (30 settings), `SettingsService` (branch→global→default), `admin.settings.*`, full UI
  - 0.2 Currencies & Rates ✅ — `CurrencyService` (effective-dated buy/sell, base-relative), `CurrencySeeder`, `admin.currencies.*`, full UI
  - 0.3 Number Sequences ✅ — `NumberSequenceService` (gapless, row-locked), 20 seeded types, `admin.sequences.*`, full UI
  - 0.4 Document Identity ✅ — vat/banking fields, `DocumentIdentityService` (branch overlay), shared print partials (`resources/views/print/partials/`)
  - 0.5 Posting Engines ✅ — `GlPostingService` (balanced-only, period gating, control-account block, reversals) + `StockLedgerService` (AVCO, negative-stock setting, reservations, `verifyIntegrity()`); COA (44 accounts) + FY periods seeded; GL/stock tables migrated (parts FK deferred to Phase 1)
  - 0.6 Shared UI Kit ✅ — `ModuleLayout` with Level-2/Level-3 sidebar resolver (`resources/js/nav/system-admin.js` is the reference config); `DataTable`, `FormField`, `StatusBadge`, `SearchInput`, `MoneyDisplay`, `ConfirmDialog`, `EmptyState`, `FlashToasts` in `resources/js/Components/`
  - 0.8 Print/Email ✅ — DomPDF, `DocumentPdfService` (identity+settings into every print view), proof template, `DocumentMail` queued base mailable, `PrintButton`
  - 0.9 Page Guides ✅ — `resources/guides/{route}.md` + DB overrides via `PageGuideService`, shared automatically per route, `<PageGuide>` (`F1`), in-app docs viewer `/help/docs/{path}`
  - 0.10 Command Palette ✅ — `Ctrl+K` `<CommandPalette>`, `/search` endpoint, `config/search_pages.php` registry (entity sources plug in per module)
  - 0.11 Notifications ✅ core — `config/notification_events.php` catalogue, `NotificationRouterService` (bell/email/SMS-logged, branch-scoped, unrouted-logs), `<NotificationBell>` in both layouts; matrix/feed/delivery-log screens pending with admin-screens batch
  - Domain exceptions: `DomainException` base + MissingExchangeRate/UnbalancedJournal/ClosedPeriod/NoOpenPeriod/ControlAccountPosting/InsufficientStock
  - Test suite: 91 passing (sqlite :memory:)
- **Docs:** complete — all modules/sub-modules, workflows, design (incl. per-sub-module sidebars), operations, tasks.
- **PHASE 0 COMPLETE** (stragglers tracked in tasks: SystemRoute permission seeds, page-guide/notification admin screens, legacy AdminLayout migration).
- **PHASE 1 CORE COMPLETE (2026-09-23):** Vehicle Reference + Inventory master data live end-to-end — exit flow verified in browser (Hilux 2.8 GD-6 → 7 fitting parts → part detail → 24 in stock @ Harare Main, bin A-01-01):
  - Migrations: vehicle makes/models/variants/engine_codes; part categories/brands/units; parts (+FK retrofit to stock tables); cross-refs; supersessions; fitments; bin_locations (+primary bin on stock_levels)
  - Seed packs (`database/data/*.csv`, `ReferenceDataSeeder`): 32 makes · 108 models · 38 variants · 50 engines · 48 categories · 34 brands · 8 units — SA/Zim car parc per local-data-seeding.md; `DemoPartsSeeder` (12 real parts, fitments, cross-refs, opening stock via StockLedgerService)
  - `Part::scopeSearch` (number/OEM/barcode/description/cross-ref) + `resolveCurrent()` supersession chain; `PartFitment::scopeForVehicle`
  - Controllers: `VehicleRef/*` (makes, models+variants, engines, FitmentLookup, CrossReference), `Inventory/*` (Part CRUD+relations, categories, brands, bins, stock levels) — 30 routes
  - 13 screens on ModuleLayout with per-sub-module sidebars (`nav/inventory.js`, `nav/vehicle-reference.js`); cards Active; 4 new page guides; palette parts source
  - Tests: suite at 106 passing (15 new Phase 1 feature tests incl. fitment year-ranges, supersession chains, cross-ref search)
- **PHASE 2 COMPLETE (2026-09-23):** the full procure-to-pay chain, browser-verified end-to-end (supplier created → PO-20260923-0001 → GRN posted: stock 36→56, AVCO 3.20→3.2714, DR 1310/CR 2120 = 68 → invoice GUD-INV-4471 3-way matched → AP 78.20 → reorder report → draft PO-20260923-0002; stock-integrity check: 0 mismatches):
  - Suppliers module: profiles/contacts (SUPP sequence), CSV price-list **import wizard** (preview → column map → part matching via number/OEM/cross-refs → one-active-per-supplier), approved suppliers with inline preferred-supplier control
  - Purchasing module: PO lifecycle + printable PDF, GRN receiving (`GrnPostingService`: tolerance/over-receipt approval, rejects never enter stock, AVCO + GL accrual), supplier invoices (`SupplierInvoiceService`: 3-way match max(2%,$10), disputed state), returns (`StockMovementService`: RMA gate, credits with variance→5300)
  - Inventory additions: adjustments (write-off journals), transfers (source-AVCO valuation, dispatch/receive), reorder report (`ReorderService`) with inline reorder-point editing + one-click draft POs
  - 16 new tables/models, 6 services, 15 screens on contextual sidebars, 5 page guides; cards Active
  - Test suite: **119 passing** incl. `ProcureToPayFlowTest` (13 golden-flow tests)
  - Deferred per plan (Phase 6): requisitions, import shipments, price comparison, supplier performance, XLSX import
- **PHASE 3 CORE COMPLETE — GO-LIVE (2026-09-23):** Customers + Sales & POS live end-to-end; browser-verified cash sale `INV-20260923-0001` (Till 7.36 / Sales 6.40 / VAT 0.96 / COGS 3.27 at AVCO; stock 56→55) → credit note `CN-20260923-0001` (restock→56, Till net 0); 0 stock-integrity mismatches.
  - Services: `PricingService` (customer→group→default price, `UnpricedPartException`, `computeLine`), `SalesPostingService` (`postInvoice`: server-priced lines, split tender, credit/hold gates, SALE stock + balanced GL 1110/1120/1210·4100+2210·5100/1310 at AVCO; `postCreditNote`: qty caps, RETURN_IN restock, VAT at original rate, cash/account refund), `SalesQuoteService` (quote+expiry → convert reserves stock → cancel releases), `StockTakeService` (snapshot→count→review→post one adjustment)
  - Models/migrations: `price_lists`, `price_list_items`, `customer_groups`, `customers` (walk-in Cash Customer, `arBalance()`, `effectivePriceList()`), `sales_documents`/`_lines`/`sales_payments`, `stock_takes`/`_lines`; `SalesSeeder` (retail+trade lists, demo prices, Cash Customer, demo trade account)
  - HTTP/UI: Customers (`customers.*`), Sales (`sales.pos|invoices|credit-notes|quotes|orders|price-lists.*`), Stock Takes (`inventory.stock-takes.*`); 20+ screens on `nav/sales.js`+`nav/customers.js` (+ stock-takes on `nav/inventory.js`); A4 invoice + 80mm receipt + credit-note + quote print templates; 9 page guides; cards Active
  - Tests: **140 passing** (+21: `CashSaleFlowTest`, `CreditSaleFlowTest`, `CustomerReturnFlowTest`, `QuoteToInvoiceFlowTest`, `StockTakeVarianceTest`)
  - Deferred (see tasks 02/05): suspend-recall, X/Z-reads, proforma/COPY-watermark, customer addresses/contacts/vehicles, statements (Phase 4), promotions/lay-by/delivery notes, SystemRoute permission seeds, per-user active-branch switching (POS trades on main branch)
- **PHASE 4 CORE COMPLETE (2026-09-23):** the accountant's surface on the already-correct ledger — browser-verified trial balance **balanced**, balance sheet **balances** (incl. contra accounts), full AR loop (`INV-20260923-0002` → `RCP-20260923-0001` → Debtors 0, Bank +82.80, integrity clean).
  - Services: `LedgerReportService` (balances-by-account, trial balance, P&L, balance sheet, GL enquiry with running balance — section-convention signing so contra accounts net right), `ArReceiptService` (receipts DR bank/CR 1210, allocation, ageing), `ApPaymentService` (payments DR 2110/CR bank, allocation, `runBatch` payment run, ageing)
  - Migrations/models: `customer_receipts`+`receipt_allocations`, `supplier_payments`+`payment_allocations`, `vat_returns`; `Customer::arBalance()`/`Supplier::apBalance()` now net receipts/payments
  - HTTP/UI: `finance.*` (coa, periods, gl, journals, receipts, payments, payment-run, reports) — 15 screens on `nav/finance.js`; manual journals block control accounts + reverse-only corrections; period open/close/lock; deprecated `FinanceSidebar.jsx` deleted; 8 page guides; Finance card + sub-cards Active
  - Tests: **154 passing** (+14: `ArReceiptFlowTest`, `ApPaymentFlowTest`, `FinancialReportsTest` incl. contra-account balance sheet)
  - Deferred (see tasks 07): 7.5 bank rec, 7.6 VAT returns UI (table migrated), 4.8 month-end close, 4.9 system health dashboard, EFT CSV export, statement/remittance PDFs, SystemRoute permission seeds
- **PHASE 5 CORE COMPLETE (2026-09-23):** Workshop runs end-to-end and integrates with Sales + Finance — browser-verified job `JC-2026-00001` (labour + issued part) → completed → invoiced `INV-20260923-0003` ($30.36, parts→4100 + labour→4300 split), Highway AR +30.36, 0 stock-integrity mismatches.
  - Services: `JobCardService` (lifecycle `JobCard::FLOW`, labour, parts request/**issue** JOB_CARD_OUT+COGS/**return** reversal), `WorkshopInvoiceService` (completed job → **revenue-only** tax invoice, COGS already booked at issue; make/labour split); `LabourCode::resolveRate` (model→make→default), `JobCard` costing/`marginPct`
  - Migrations/models: `customer_vehicles`+`vehicle_service_history`, `labour_codes`+`labour_code_rates`, `technicians`, `job_cards`+`job_card_labours`+`job_card_parts`+`technician_time_logs`; `sales_document_lines.part_id` made **nullable** + `line_type`/`labour_code` (so invoices carry labour lines)
  - HTTP/UI: `workshop.*` (board, jobs, vehicles, labour, technicians) — 9 screens on `nav/workshop.js`; job detail hub (status machine buttons, technician assign, labour + parts issue/return, live costing + invoice panel), kanban board; `WorkshopSeeder` (labour pack, 2 technicians, demo vehicle); 4 page guides; Workshop card + sub-cards Active
  - Fixed in browser: workshop part lookup now resolves the retail selling price (was defaulting billed price to 0)
  - Tests: **160 passing** (+6: `JobCardFlowTest` — issue/COGS, revenue-split invoice, costing margin, return reversal, allocate-gate, make-specific rate)
  - Deferred (see tasks 04): 4.7 warranty claims, workshop/job-card print templates, promised-time alerts, clock-on/off KPIs, auto-requisition, SystemRoute permission seeds
- **PHASE 6 CORE COMPLETE (2026-09-23):** the reporting surface is live on real cross-phase data — browser-verified executive dashboard (KPIs $113.16 MTD sales, $30.36 AR, $2,377 stock, charts) and inventory report (stock value **GL-reconciliation check** correctly flags un-journalised demo opening stock).
  - `ReportService` (KPIs, sales trend this/last year, AR/AP ageing reuse, top parts/categories, sales summary by method, sales by customer, stock value+reconcile+on-hand+ageing, top/dormant customers + credit review, supplier spend + open POs, workshop productivity + job profitability)
  - Lightweight inline-SVG chart kit `resources/js/Components/Charts.jsx` (StatTile/LineChart/Bars/LabelledBars/HBarList) — **no external chart dependency**
  - HTTP/UI: `reports.*` (dashboard, sales, inventory, customers, suppliers, workshop) — 6 screens on `nav/reports.js`; Reports card + sub-cards Active; Financial-Reports card points at the Phase 4 `finance.reports.*`; 2 page guides
  - Tests: **165 passing** (+5: `ReportServiceTest` — KPIs, net-of-credit-notes, top parts/category, stock-value GL reconcile, sales-by-method)
  - Bug fixed: date-column filters use `whereDate` (not `whereBetween`, which mis-compared the stored `Y-m-d 00:00:00` upper bound)
  - Deferred (see tasks 09): 9.4 extra financial reports, 9.8 scheduled reports, Excel/PDF export, ABC/dead-stock, technician efficiency
- **PHASE 7 STARTED — System Health gate live (2026-09-23) ★:** the go-live integrity dashboard is built and browser-verified. Financial + stock invariants recompute live from the ledger; a one-click Fix posted the opening-inventory journal (DR 1310 / CR 3300) and flipped the board to **"All systems healthy" (9/9)**, reconciling the last cross-phase variance.
  - `app/Services/Health/{HealthResult,HealthCheckRunner}` (journals balanced, TB balanced, AR=1210, AP=2110, stock=1310, period open, stock-levels=ledger, negative stock, stale draft POs — each with fix/link routes), `OpeningBalanceService` (idempotent opening-inventory migration), `admin.health` screen + Governance nav + System-Admin card Active, page guide
  - Tests: **169 passing** (+4: `SystemHealthTest`)
  - **Go-live checklist remaining** (operational / deferred): health-check persistence + nightly run + notification routing + operations checks (queue/backup/disk/scheduler), bank reconciliation, VAT-returns UI, warranty claims, promotions/lay-by/loyalty, purchase requisitions, **SystemRoute permission seeds** + permission audit, backup+restore drill, hardware/printer setup, Excel/PDF report export, scheduled reports.
- **Every sub-module card is now Active (2026-09-23):** built out the full remaining backlog so no card is greyed. New tables/models/controllers/screens: Communication Log, Loyalty (`customers.comms|loyalty`); Technical Bulletins (`vehicle-ref.bulletins`); Promotions, Lay-bys, Delivery Notes (`sales.promotions|laybys|delivery-notes`); Import Management, Price Comparison (`purchasing.imports|price-comparison`); Serial & Batch (`inventory.serials`); Cash & Bank + reconciliation (`finance.bank`); Scheduled Reports (`reports.scheduled`); Warranty Claims (`workshop.warranty`); and the admin config screens Password Policy, Email & SMS, Print Templates, Notifications Centre, Backup Management (`admin.*`, driven by new `security.*`/`comms.*` settings). Migrations `2026_01_07_000001/000002`. NOTE: some are functional-but-minimal (Scheduled Reports & Backup have no live scheduler/off-site yet; SMS needs a gateway; bank import is manual entry) — these are honest working stubs, not fakes, and their remaining depth is tracked in the module task files.
- **Sub-module fill-in (2026-09-23):** activated the cards backed by real data/models so fewer sit "Coming Soon" — VAT Management (`finance.vat.*`, tested), Customer Statements (PDF), Customer Vehicles → registry, Credit Management → customer report, Job Costing → workshop report, **Approved Supplier List / Supplier Contacts / Supplier Performance** (`suppliers.*`), **Supplier Credit Notes** (`purchasing.supplier-credits`), **Supersession Management** (`vehicle-ref.supersessions`), plus a **10,224-part catalogue** (`CatalogueSeeder`). ~66/83 sub-modules now Active. Genuinely-deferred (need feature/infra builds, kept honestly greyed): serial/batch, promotions, lay-by, delivery notes, import mgmt, price comparison, warranty claims, loyalty, comm log, bank rec, technical bulletins, scheduled reports, print templates, email/SMS, notifications-centre UI, backup, password policy.
- **The six core build phases are complete** — SparesPro trades (POS/quotes/orders/invoices/credit notes), procures (PO→GRN→3-way match→AP), keeps double-entry books (AR/AP/GL/reports, GL-reconciled), runs a workshop (job cards→labour+parts→invoice with costing), reports on all of it (dashboard + suites), and proves its own integrity (System Health). Remaining work is the go-live hardening checklist above.
- **Volume demo data for testing (2026-09-23) — `DemoDataSeeder` (dev-only, `php artisan db:seed --class=DemoDataSeeder`):** loads thousands of records across every section and leaves **System Health green (9/9)**. Non-financial master data is bulk-inserted; **every financial transaction posts through the real services** (sales/receipts/credit notes, PO→GRN→supplier-invoice→payment, workshop job→invoice) so AR/AP/stock/GL stay reconciled. Seeded volumes: ~1,000 customers, 200 suppliers (+411 contacts, 801 approved links), 801 vehicles, 22 technicians, 900 comms-log, 800 loyalty, 900 serials, 120 lay-bys, 100 warranty claims, 60 bulletins, 40 promotions, 3 bank accounts + 400 statement lines, 60 imports, 200 delivery notes; **4,739 stocked parts** (opening stock via ledger, GL-reconciled through `OpeningBalanceService`); financial docs: **1,251 invoices** (cash + account + workshop), 500 quotes, 180 orders, 121 credit notes, 177 receipts, 133 POs/131 GRNs/131 supplier invoices, 82 supplier payments, 221 job cards. Seeder is resumable (per-section count guards) and dev/non-prod guarded; not wired into `DatabaseSeeder` (manual invoke only). NOTE: it must stay service-posted — raw-inserting financial docs would break the reconciliation invariants the System Health board checks.
