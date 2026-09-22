# Tasks: Foundations (System Administration extensions + engines)

> Phase 0 of the [implementation plan](../implementation-plan.md). Spec: [System Administration module doc](../modules/10-system-administration.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

## 0.1 Configuration Centre  ·  [spec](../configuration-centre.md)

### Backend
- [ ] Migration(s): settings registry table + per-branch override table (key, type, group, default, branch value)
- [ ] Models + relationships + factories
- [ ] `SettingsService`: typed get/set with branch-override resolution (branch value → global value → registry default) + cache invalidation
- [ ] Form Requests + Policies
- [ ] Controller + routes (`admin.settings.*`) + SystemRoute permission seeds
### Frontend
- [ ] Configuration Centre page: grouped settings editor with per-branch override toggle
- [ ] Sidebar nav entry in System Admin module nav config
### Tests
- [ ] Feature tests: branch override wins over global; unknown key rejected; cache busts on save
- [ ] Unit tests: `SettingsService` resolution order and type casting
- [ ] Functional pass: set a global default, override it for one branch, confirm the other branch still reads the default

## 0.2 Currencies + Exchange Rates  ·  [spec](../modules/10-system-administration/10.12-currencies.md)

### Backend
- [ ] Migration(s): `currencies`, exchange rates table (rate per currency pair per date)
- [ ] Models + factories + seeder for USD / ZWG / ZAR with USD as base
- [ ] `CurrencyService`: rate lookup by date, conversion, rounding rules
- [ ] Form Requests + Policies + Controller + routes (`admin.currencies.*`) + SystemRoute permission seeds
### Frontend
- [ ] Currencies page: list + edit, daily rate capture form, rate history
- [ ] Sidebar nav entry in System Admin module nav config
### Tests
- [ ] Feature tests: rate effective-dating (yesterday's rate used for yesterday's document); base currency cannot be deleted
- [ ] Unit tests: `CurrencyService` conversion + rounding
- [ ] Functional pass: capture today's ZWG rate, convert an amount both directions, verify rounding

## 0.3 Number Sequences  ·  [spec](../modules/10-system-administration/10.9-number-sequences.md)

### Backend
- [ ] Migration(s): number sequences table (document type, prefix, format e.g. `INV-YYYYMMDD-XXXX`, next number, per-branch flag)
- [ ] `NumberSequenceService`: atomic, gapless allocation under concurrency (row lock inside transaction)
- [ ] Seeder: one sequence per document type (quote, order, invoice, credit note, PO, GRN, adjustment, transfer, stock take, PR, supplier return, payment, statement)
- [ ] Controller + routes (`admin.sequences.*`) + SystemRoute permission seeds
### Frontend
- [ ] Number sequences page: list + edit prefix/format/next-number (with guard against lowering next number)
- [ ] Sidebar nav entry in System Admin module nav config
### Tests
- [ ] Feature tests: gapless invoice numbering (VAT compliance rule from Sales 2.4)
- [ ] Unit tests: parallel allocation produces no duplicates and no gaps
- [ ] Functional pass: hammer the service from two concurrent requests, inspect the issued numbers

## 0.4 Company / Branch Document Identity  ·  [spec](../modules/10-system-administration/10.2-company-setup.md)

### Backend
- [ ] Migration(s): extend company + branch tables with logo path, VAT number, tax number, banking details, printed footer text
- [ ] `DocumentIdentityService` (or view-composer): resolves branch-level identity with company fallback for all printed docs
- [ ] Form Requests + Policies + Controller + routes (`admin.company.*`) + SystemRoute permission seeds
### Frontend
- [ ] Company setup page: identity, logo upload, VAT/banking fields; per-branch override section
### Tests
- [ ] Feature tests: branch with no logo falls back to company logo; VAT number appears in identity payload
- [ ] Functional pass: upload a logo, render the proof PDF (0.8), see logo + VAT + banking on it

## 0.5 StockLedgerService + GlPostingService Skeletons

### Backend
- [ ] Migration(s): `stock_ledger` (append-only), `stock_levels` cache table; GL journal + journal lines tables
- [ ] `StockLedgerService`: `post()` writes ledger entry (all transaction types from Inventory 1.2), maintains `running_balance`, updates `stock_levels` cache, enforces negative-stock block unless branch `allow_negative_stock`
- [ ] `GlPostingService`: `post()` accepts a balanced journal (debits = credits or reject), writes journal + lines, no UI yet
- [ ] AVCO cost calculation hook in `StockLedgerService` (weighted average recomputed on every IN movement)
### Tests
- [ ] Unit tests: ledger is append-only (update/delete throws); running balance correct across mixed IN/OUT; AVCO recompute; unbalanced journal rejected
- [ ] Feature test (exit criterion): a test posts a balanced GL journal and a stock ledger entry end-to-end
- [ ] Functional pass: tinker session — post OPENING_BALANCE then SALE, verify `stock_levels.qty_on_hand` equals ledger sum

## 0.6 Shared UI Kit

### Frontend
- [ ] `ModuleLayout` + sidebar config system (per-module nav config consumed by layout, permission-filtered)
- [ ] `DataTable` (server-side sort/filter/paginate, column config)
- [ ] `FormField` (label, error, help-text wrapper for inputs/selects)
- [ ] `StatusBadge` (status → colour map, used by every document list)
- [ ] `SearchInput` (debounced, keyboard-friendly for POS reuse)
- [ ] `MoneyDisplay` (currency-aware formatting via 0.2)
- [ ] `ConfirmDialog` (destructive-action confirmation)
### Tests
- [ ] Component tests: DataTable sorting/pagination props; MoneyDisplay formatting per currency
- [ ] Functional pass: scaffold a throwaway module page with the kit in under an hour (exit criterion)

## 0.7 Dashboard Module Cards

### Frontend
- [ ] Replace school cards with the 10 SparesPro module cards (Inventory, Sales & POS, Purchasing, Workshop, Customers, Suppliers, Finance, Vehicle Reference, Reports, System Admin)
- [ ] Permission-filter: card hidden when user has no route access in that module
### Tests
- [ ] Feature tests: user with inventory-only permissions sees only Inventory (+ allowed) cards
- [ ] Functional pass: log in as restricted role, verify card set matches permissions

## 0.8 Print/PDF + Email Pipeline  ·  [specs](../modules/10-system-administration/10.10-print-templates.md) · [email](../modules/10-system-administration/10.11-email-configuration.md)

### Backend
- [ ] DomPDF install + base document template (company identity from 0.4, one proof template rendered end-to-end)
- [ ] Print template registry: A4 document + 80mm thermal receipt layouts as named templates
- [ ] Email pipeline: mailable base class + configurable SMTP settings + queued send with PDF attachment
### Frontend
- [ ] Print/download button pattern (shared component) usable from any document page
### Tests
- [ ] Feature tests: proof template renders with logo/VAT/banking; email queues with PDF attached
- [ ] Functional pass: download the proof PDF and send it to a mailtrap inbox

## 0.9 Page Guide System  ·  [spec](../design/page-guide.md)

### Backend
- [ ] Guide loader: shipped markdown per route (`Modules/{X}/resources/guides/{route}.md`) + `page_guides` DB override table (migration, model, admin CRUD `admin.page-guides.*` + permission seeds)
- [ ] In-app docs viewer: read-only markdown renderer for `docs/**` (route `help.docs.show`) so guides deep-link specs
### Frontend
- [ ] `<PageGuide>` component: collapsed header trigger, `F1` open / `Esc` close, in-place expand (150 ms), markdown render with `guide:`/`route:`/`doc:` link resolver, permission-aware links
- [ ] Mounted in `ModuleLayout` + `AppLayout` header rows; archetype content templates documented for module authors
- [ ] Guides written for all Phase 0 screens (dashboard, configuration centre, currencies, sequences)
### Tests
- [ ] Feature tests: DB override wins over shipped file; denied-route link renders as locked text
- [ ] Functional pass: `F1` on Configuration Centre expands guide; link jumps to Currencies page; `Esc` collapses without losing unsaved form state

## 0.10 Global Search / Command Palette  ·  [spec](../design/global-search-and-quick-actions.md)

### Backend
- [ ] `GlobalSearchController`: pluggable per-entity sources (register parts/customers/documents/vehicles as their modules land; Phase 0 ships pages + users source), branch-scoped, ≤15 rows, prefix operators (`p:`, `c:`, `d:`, `v:`, `>`)
### Frontend
- [ ] `<CommandPalette>`: `Ctrl+K` global mount in both layouts, grouped results, recent items (localStorage ids, re-fetched), keyboard nav, `Ctrl+Enter` new tab, quick-action rows
### Tests
- [ ] Feature tests: permission filtering (restricted user sees no finance documents); prefix operator scoping
- [ ] Functional pass: `Ctrl+K` from any page finds a page by name and navigates; recent item appears on next open

## 0.11 Notifications Centre skeleton  ·  [spec](../modules/10-system-administration/10.13-notifications-centre.md)

### Backend
- [ ] Migrations: `notification_routes`, `notification_deliveries` (+ Laravel native `notifications`)
- [ ] Typed event catalogue registry (modules declare events like settings) + routing resolver + queued email/SMS channels with retry + delivery log
- [ ] Routing matrix CRUD (`admin.notifications.*`) + permission seeds; storm-collapse (summary notification over N same-type events)
### Frontend
- [ ] Bell dropdown in both layouts (unread count, deep links, mark-read-on-visit) + `notifications.index` feed + routing matrix screen + delivery log with retry
### Tests
- [ ] Feature tests: routed event lands in recipient feed; unrouted event still logs; mandatory events unmutable; digest batching
- [ ] Functional pass: trigger a test event from the matrix screen ("send test"), see bell update, click through to linked record marks it read

## Deferred (Phase 6)
- [ ] Scheduled report email delivery rides on this pipeline (9.8) — build in Phase 6
- [ ] SMS gateway integration + customer-facing SMS events (job-complete SMS) — with Workshop (Phase 5)
