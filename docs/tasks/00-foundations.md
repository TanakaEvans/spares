# Tasks: Foundations (System Administration extensions + engines)

> Phase 0 of the [implementation plan](../implementation-plan.md). Spec: [System Administration module doc](../modules/10-system-administration.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

## 0.1 Configuration Centre  ·  [spec](../configuration-centre.md)

### Backend
- [x] Migration(s): `settings` table with (key, branch_id) unique — registry is code-declared in `config/settings_registry.php` (30 core settings, 10 groups) — done 2026-09-22
- [x] Models + relationships + factories (`Setting`, plus `BranchFactory`/`CompanyFactory`) — done 2026-09-22
- [x] `SettingsService`: typed get/set, branch → global → default resolution, cache + invalidation, revertToGlobal — done 2026-09-22
- [x] Validation via declared per-key rules in controller (registry-driven; dedicated FormRequest unnecessary) — done 2026-09-22
- [x] Controller + routes (`admin.settings.index/update/revert`) behind admin middleware — done 2026-09-22 *(SystemRoute permission seeds pending with role-seed task below)*
### Frontend
- [x] Configuration Centre page: group rail + search, typed inputs, branch selector, override/inherits badges, revert-to-global — done 2026-09-22
- [x] Reachable from System Admin module card grid (`modules.js`); sidebar nav entry follows with ModuleLayout (0.6) — done 2026-09-22
### Tests
- [x] Feature tests (8): render, guest redirect, global save, branch override, unknown key rejected, rules enforced, global-only override rejected, revert — done 2026-09-22
- [x] Unit tests (11): resolution order, casting, cache bust, revert, unknown key, per-branch guard — done 2026-09-22
- [x] Functional pass — set global rows-per-page 25→40 in browser, saved & persisted; branch view showed "Inherits global"/locked global-only settings — done 2026-09-22

## 0.2 Currencies + Exchange Rates  ·  [spec](../modules/10-system-administration/10.12-currencies.md)

### Backend
- [x] Migration(s): `currencies` + `exchange_rates` (base-relative, dated, buy/sell sides, unique per currency+date) — done 2026-09-22
- [x] Models + factories + `CurrencySeeder` (USD base + ZWG/ZAR active + 7 regional inactive) — done 2026-09-22
- [x] `CurrencyService`: effective-dated rate lookup (≤ date), toBase/fromBase with per-currency rounding, `hasRateForToday`, `captureRate`; `MissingExchangeRateException` (first `DomainException`) — done 2026-09-22
- [x] Controller + routes (`admin.currencies.index/rates.store/toggle`) with validation (sell ≥ buy, no future dates, base takes no rates) — done 2026-09-22 *(SystemRoute seeds pending with role-seed task)*
### Frontend
- [x] Currencies page: table with latest buy/sell + rate-date freshness, missing-today warning banner, rate capture form, recent-rates history, activate/deactivate — done 2026-09-22
- [x] Reachable from System Admin module cards (`modules.js`); sidebar entry follows ModuleLayout (0.6) — done 2026-09-22
### Tests
- [x] Feature tests (7): render, capture, sell≥buy, base rejects rates, future date rejected, toggle, base cannot deactivate — done 2026-09-22
- [x] Unit tests (7): base=1.0, missing-rate exception, effective dating, historical stability, round-trip conversions, today check, same-day update-not-duplicate — done 2026-09-22
- [x] Functional pass — captured ZWG 26.00/26.80 in browser, persisted with audit user; warning banner cleared for ZWG — done 2026-09-22

## 0.3 Number Sequences  ·  [spec](../modules/10-system-administration/10.9-number-sequences.md)

### Backend
- [x] Migration: `number_sequences` (type, branch nullable, prefix, date segment, padding, reset frequency; unique type+branch) — done 2026-09-22
- [x] `NumberSequenceService`: `next()` with `lockForUpdate` inside the caller's transaction (rollback releases the number — gapless), `peek()` preview, branch-sequence-wins-with-global-fallback, yearly/monthly resets — done 2026-09-22
- [x] Seeder: 20 document types (invoice, quote, SO, CN, DN, receipt, PO, PR, GRN, supplier return, job card, stock take/adjustment/transfer, payment, journal, warranty, lay-by, customer, supplier) — done 2026-09-22
- [x] Controller + routes (`admin.sequences.index/update`) — next_number deliberately NOT editable via UI (gapless guard) — done 2026-09-22 *(SystemRoute seeds pending with role-seed task)*
### Frontend
- [x] Sequences page: full table with live next-number previews, inline edit (prefix/date segment/digits/reset) — done 2026-09-22
- [x] Reachable from System Admin module cards (`modules.js`); sidebar entry follows ModuleLayout (0.6) — done 2026-09-22
### Tests
- [x] Feature tests (3): render with 20 seeded sequences + previews, format update, invalid prefix rejected — done 2026-09-22
- [x] Unit tests (9): sequential formatting, no-date variant, peek non-consuming, branch-wins/global-fallback, unconfigured throws, yearly reset + same-year no-reset, **rollback releases number gaplessly** — done 2026-09-22
- [x] Functional pass — sequences screen verified in browser showing INV-20260922-0001, JC-2026-00001, CUST-00001 previews — done 2026-09-22

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
- [ ] Sub-module contextual sidebar resolver: route-prefix → `nav.js` `subModules` entry (WORK/INSIGHTS/SETUP/QUICK LINKS sections, ↰ back to module menu, badges, auto-collapse flag) per [design/sidebars/README.md](../design/sidebars/README.md)
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
