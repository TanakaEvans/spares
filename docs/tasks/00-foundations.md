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
- [x] Migration: vat_number + banking on companies; logo + banking on branches (logo/tax existed; footer text lives in Configuration Centre `documents.*`) — done 2026-09-23
- [x] `DocumentIdentityService::for(?branch)` — branch overlay with company fallback; statutory fields always company-level — done 2026-09-23
- [x] `admin.company.*` routes existed; validation extended for vat_number + banking — done 2026-09-23
- [x] Shared print partials: `print/partials/document-header.blade.php`, `document-footer.blade.php` (banking + settings footer), `document-header-thermal.blade.php` (80mm) — done 2026-09-23
### Frontend
- [x] Company page extended: VAT Registration Number + Banking Details section — done 2026-09-23 *(logo upload pre-existing; per-branch overrides via branch edit form when Branch page is next touched)*
### Tests
- [x] Unit tests (3): company identity, branch overlay + fallback, statutory-fields-from-company — done 2026-09-23
- [ ] Functional pass: render the proof PDF with logo + VAT + banking — deferred to 0.8 (print pipeline), where the proof template lands

## 0.5 StockLedgerService + GlPostingService Skeletons

### Backend
- [x] Migrations: `gl_accounts/gl_years/gl_periods/gl_journals/gl_journal_lines` + `stock_levels`/`stock_ledger` (part_id FK deferred to Phase 1 parts migration) — done 2026-09-23
- [x] `StockLedgerService`: all 10 movement types, positive-qty API with type-driven sign, running_balance, row-locked cache update, per-branch negative-stock setting, reservations (reserve/release), `verifyIntegrity()` health check — done 2026-09-23
- [x] `GlPostingService`: balanced-or-reject, open-period gating (`ClosedPeriodException`/`NoOpenPeriodException`), control-account direct-posting block with sub-ledger bypass, sequenced JNL numbers, `reverse()` counter-journals, normal-balance-aware `accountBalance()` — done 2026-09-23
- [x] AVCO weighted-average on every IN; issues move at AVCO without changing it — done 2026-09-23
- [x] Seeders: `ChartOfAccountsSeeder` (44 accounts from Module 7 COA), `FinancialPeriodSeeder` (FY + 12 open periods) — done 2026-09-23
- [x] Domain exceptions: Unbalanced/ClosedPeriod/NoOpenPeriod/ControlAccountPosting/InsufficientStock — done 2026-09-23
### Tests
- [x] Unit tests (20): balanced posting, unbalanced-writes-nothing, control-account block + sub-ledger bypass, closed/missing period, bad lines, reversal zero-net, normal-balance sides; AVCO recompute, sale-at-AVCO, negative block + per-branch allow, mixed running balances, reservations, integrity check, branch isolation — done 2026-09-23
- [x] Exit criterion met: tests post a balanced GL journal and stock ledger entries end-to-end — done 2026-09-23
- [x] Functional pass: full suite (70 tests) green incl. cache-vs-ledger integrity verification — done 2026-09-23

## 0.6 Shared UI Kit

### Frontend
- [x] `ModuleLayout` (`Layouts/ModuleLayout.jsx`): dark slate-900 sidebar per component-standards §1a, top bar with breadcrumb, mobile overlay, `FlashToasts` mounted — done 2026-09-23
- [x] Sub-module contextual sidebar resolver: route-prefix → `subModules` entry with WORK/INSIGHTS/SETUP/QUICK LINKS ⇄ sections, orange context header, ↰ back to module landing, badge slots — first real config `resources/js/nav/system-admin.js`; three admin pages converted — done 2026-09-23 *(auto-collapse flag lands with the first operational screen; permission-filtering of items lands with role-seed task)*
- [x] `DataTable` (column config, row-as-link per UI-13, Laravel paginator footer, empty state) — done 2026-09-23 *(server-side sort wiring comes with first Phase-1 list screen)*
- [x] `FormField`, `StatusBadge` (the single status→colour map), `SearchInput` (debounced, Enter-immediate for scanners), `MoneyDisplay` (negatives in red parens, tabular-nums), `ConfirmDialog` (Cancel-focused, destructive variant), `EmptyState`, `FlashToasts` (success auto-dismiss, errors persist) — done 2026-09-23
### Tests
- [x] Covered via full suite remaining green after layout conversion (70 passing); component render verified in browser — done 2026-09-23
- [x] Functional pass / exit criterion: three real pages (Settings, Currencies, Sequences) run on the kit — Level-3 sidebar, breadcrumbs, quick-link jumps and rate capture all verified in browser — done 2026-09-23
- [ ] Follow-up: migrate legacy AdminLayout pages (Company, Branches, Departments, Users, Roles) onto `ModuleLayout` — tracked in [tasks/10-system-admin.md](10-system-admin.md)

## 0.7 Dashboard Module Cards

### Frontend
- [ ] Replace school cards with the 10 SparesPro module cards (Inventory, Sales & POS, Purchasing, Workshop, Customers, Suppliers, Finance, Vehicle Reference, Reports, System Admin)
- [ ] Permission-filter: card hidden when user has no route access in that module
### Tests
- [ ] Feature tests: user with inventory-only permissions sees only Inventory (+ allowed) cards
- [ ] Functional pass: log in as restricted role, verify card set matches permissions

## 0.8 Print/PDF + Email Pipeline  ·  [specs](../modules/10-system-administration/10.10-print-templates.md) · [email](../modules/10-system-administration/10.11-email-configuration.md)

### Backend
- [x] DomPDF ^3.1 installed; `DocumentPdfService::render(view, data, branch)` merges identity + settings (footer text, VAT rate) into every print view; proof template end-to-end (`admin.print.proof`) — done 2026-09-23
- [x] Template skeleton: A4 via shared header/footer partials (0.4) + 80mm thermal partial ready; named-template registry deferred to first real document (invoice, Phase 3) where it has content to register — done 2026-09-23
- [x] Email pipeline: `DocumentMail` base mailable (queued `emails` queue, 3 tries, PDF attachment via `Attachment::fromData`) + shared identity-branded HTML layout (`mail/document.blade.php`); SMTP via `.env`/config — done 2026-09-23
### Frontend
- [x] `PrintButton` shared component (new-tab stream, never blocks the page) — done 2026-09-23
### Tests
- [x] Feature tests (4): proof streams valid `%PDF` with identity; rendered HTML contains company/VAT/bank; mail queues with attachment; mail body renders identity — done 2026-09-23
- [x] Functional pass: proof PDF rendered (879 KB) and delivered for visual inspection — identity, lines, VAT-from-settings, banking footer all present — done 2026-09-23 *(live SMTP send happens at deployment when real credentials exist)*

## 0.9 Page Guide System  ·  [spec](../design/page-guide.md)

### Backend
- [x] Guide loader: shipped markdown in `resources/guides/{route}.md` + `page_guides` DB override (override wins) via `PageGuideService`; guide auto-shared per route through `HandleInertiaRequests` — done 2026-09-23
- [ ] Admin override editor UI (`admin.page-guides.*`) — DB layer + service ready; small editor screen follows with the legacy-layout migration batch
- [x] In-app docs viewer (`help.docs.show`, `/help/docs/{path}`): read-only markdown for `docs/**`, traversal-blocked — done 2026-09-23
### Frontend
- [x] `<PageGuide>`: header `ⓘ` trigger + `F1`/`Esc`, in-place amber panel, marked+DOMPurify render, `route:`/`doc:` link resolver (unknown routes stay inert) — done 2026-09-23
- [x] Mounted in both `ModuleLayout` and `AppLayout`; archetype templates documented in [design/page-guide.md](../design/page-guide.md) — done 2026-09-23
- [x] Guides shipped: dashboard, module landing, configuration centre, currencies, sequences — done 2026-09-23
### Tests
- [x] Feature tests (5): shipped guide shared on page, DB override wins, null on unguided routes, docs viewer renders, traversal blocked — done 2026-09-23
- [x] Functional pass: guide opened on Configuration Centre in browser; in-guide link jumped to Currencies & Rates — done 2026-09-23

## 0.10 Global Search / Command Palette  ·  [spec](../design/global-search-and-quick-actions.md)

### Backend
- [x] `GlobalSearchService` + `/search` endpoint: pluggable sources (Phase 0: pages registry `config/search_pages.php` + users for Superuser), prefix operators (`>` pages, `u:` users; `p:/c:/d:/v:` reserved for entity modules), ≤6 rows per group — done 2026-09-23
### Frontend
- [x] `<CommandPalette>`: `Ctrl+K` in both layouts, grouped results with hints, recents (localStorage, try/catch-guarded), ↑↓/Enter/Ctrl+Enter keyboard nav, debounced + abortable fetch — done 2026-09-23 *(quick-action rows activate with the entity sources in Phase 1+)*
### Tests
- [x] Feature tests (5): label+keyword match, superuser user-search, non-superuser gets no user results, `>` scope operator, guests 401 — done 2026-09-23
- [x] Functional pass: `Ctrl+K` on dashboard → typed "sequence" → result rendered → Enter navigated to Number Sequences — done 2026-09-23

## 0.11 Notifications Centre skeleton  ·  [spec](../modules/10-system-administration/10.13-notifications-centre.md)

### Backend
- [x] Migrations: `notifications` (native feed), `notification_routes` (matrix), `notification_deliveries` (email/SMS log) — done 2026-09-23
- [x] Typed event catalogue (`config/notification_events.php`, 9 events with severity/mandatory/sms_capable) + `NotificationRouterService`: role/user resolution, branch scoping, bell via database channel, email via queued `DocumentMail` with delivery logging, SMS recorded pending gateway, unrouted events log (nothing vanishes) — done 2026-09-23
- [ ] Routing matrix CRUD screen (`admin.notifications.*`) + delivery-log screen with retry + storm-collapse — with the admin-screens batch (routes seeded manually until then)
### Frontend
- [x] Bell in both layouts: unread badge, severity dots, deep links, mark-read-on-open, mark-all-read; shared lazily via `HandleInertiaRequests` — done 2026-09-23
- [ ] Full `notifications.index` feed page — with the admin-screens batch
### Tests
- [x] Feature tests (7): bell delivery to role members, email queues + delivery logged, unrouted logs, unknown key rejected, branch scoping, inactive users skipped, SMS recorded — done 2026-09-23
- [x] Functional pass: fired `currency.daily_rate_missing` → bell badge "1" → dropdown showed alert with severity dot and deep link, verified in browser — done 2026-09-23

## Deferred (Phase 6)
- [ ] Scheduled report email delivery rides on this pipeline (9.8) — build in Phase 6
- [ ] SMS gateway integration + customer-facing SMS events (job-complete SMS) — with Workshop (Phase 5)
