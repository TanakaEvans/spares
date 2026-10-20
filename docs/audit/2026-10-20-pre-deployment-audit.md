# Pre-Deployment System Audit — 2026-10-20

> Full walk-through of SparesPro as it stands before go-live at Mokone Auto Spares (Bochum, Limpopo), measured against what a best-in-class motor-spares ERP must do in each **existing** module. No new modules are proposed — only depth, control and operability of what is already built. The resulting work is Phase 8 in [implementation-plan.md](../implementation-plan.md) and tracked in [tasks/11-go-live-hardening.md](../tasks/11-go-live-hardening.md).

---

## 1. How the audit was done

| Step | What | Result |
|------|------|--------|
| Automated suite | `php artisan test` | 179 passing, 639 assertions (incl. the new Data Upload test) |
| Clean seed | Live local DB = `migrate:fresh --seed` state + opt-in `DemoSeeder` | 10,224 parts, 1,001 customers, 5 suppliers; `DemoDataSeeder` crashed on a duplicate approved-supplier row (dev-only bug, fixed in this phase) |
| Browser walk | Logged in as the demo Manager; drove Dashboard, POS, Parts, Stock, Customers, Invoices, POs, Suppliers, Trial Balance, Workshop board, Executive dashboard, System Health, Configuration Centre, Company, Backups, Roles | Findings below |
| Code review | Authorization, services, middleware, scheduler, settings usage, print pipeline, seeders | Findings below |

---

## 2. Headline verdict

The **financial and stock core is genuinely strong**: every transaction posts through `StockLedgerService` / `GlPostingService`, the ledger reconciles (System Health 8/9 on the demo set, the one failure being un-journalised demo opening stock with a one-click fix), documents carry anti-forgery stamps, and the procure-to-pay / order-to-cash / workshop chains are tested end-to-end.

What is **not** ready for a real shop floor is the layer around that core: **access control is not enforced**, the **supervisor override at the till is a client-side flag**, there is **no audit trail**, **no scheduler, no real backups**, the **currency symbol is a hard-coded `$` in a ZAR business**, and nothing tells the operator how to deploy or whether the server is configured safely. Those are the deployment blockers; everything else is depth.

---

## 3. Deployment blockers (P0)

| # | Finding | Evidence | Risk if deployed as-is |
|---|---------|----------|------------------------|
| P0-1 | **Route permissions are not enforced.** The role→route matrix exists (`role_routes`, 310 seeded routes) and the Roles screen lets you tick routes, but no middleware checks `User::hasRouteAccess()` on requests. Only `AdminMiddleware` (role-name check for `Admin`/`Superuser`) guards the `admin.*`/`auth.*` prefixes. | 0 Policies, 0 Form Requests, 0 `authorize()` calls; `EnsureHasRole` only checks "has any role". | A Cashier can post manual journals, close periods, change prices, delete customers, run payment runs. |
| P0-2 | **Every non-superuser role has 0 permissions.** Seeded roles (Manager, Cashier, Buyer, Accountant, Workshop, Inventory Clerk, Admin) carry no routes. Once P0-1 is enforced, every staff login would see nothing. | Roles screen: `Permissions 0` on all but Superuser. | Either no control (today) or no access (after enforcement) — both unusable. |
| P0-3 | **Nothing hides what a user may not do.** Dashboard cards, sub-module cards and sidebars render for everyone regardless of permissions. | `Dashboard.jsx`, `Modules/Show.jsx`, `ModuleLayout.jsx` have no permission filter. | Staff click into screens that 403 (after P0-1) — confusing, and reveals the whole system map. |
| P0-4 | **POS supervisor override is fake.** `discount_approved` is a boolean the browser sets to `true` whenever a line exceeds the limit; the server accepts it. The settings help text promises "the POS asks for a supervisor PIN". | `Terminal.jsx:211`, `PointOfSaleController::store`. | Any cashier can give 100% discount unchallenged; no approver recorded. |
| P0-5 | **No audit trail.** Rule 10 ("every override/approval audited: who, when, before/after") has no implementation — no `audit_logs` table, no service. Only login attempts are logged. | `ls database/migrations \| grep audit` → only `auth_login_logs`. | Price changes, credit-limit edits, settings changes, role grants and overrides are untraceable. |
| P0-6 | **Base currency symbol is hard-coded `$`.** `MoneyDisplay` defaults to `$`; the whole UI shows `$2,359.60` for a ZAR company. The Company screen has its *own* legacy currency dropdown (USD/ZWL/ZAR/EUR/GBP) duplicating the Configuration Centre base currency. A settings label reads "Loyalty points per $1 spent". | Screens: POS, Stock Levels, Suppliers, Executive dashboard all show `$`. | Every screen and figure the owner sees is in the wrong currency. |
| P0-7 | **No scheduler, no real backups.** `routes/console.php` has only the `inspire` stub. "Backup now" writes a placeholder text file. The spec's nightly health run, backup age check, off-site copy and scheduler heartbeat do not exist. | `BackupController::store` writes a comment, not a dump. | A disk failure loses the books. Health is only checked when someone opens the screen. |
| P0-8 | **Security settings are not enforced.** `security.*` settings (min length, mixed case, number, expiry days, max failed attempts, lockout minutes) exist in the registry and have a screen, but login hard-codes lockout at 7 attempts, lock is permanent, and password change hard-codes a 5-month expiry. | `LoginController:43`, `ChangePasswordController:37`. | Rule 6 violation; the Password Policy screen is decorative. |
| P0-9 | **No deployment runbook or environment preflight.** `.env` is `APP_DEBUG=true`, Debugbar is on, `APP_NAME="SparesPro ERP"`, `APP_URL=http://localhost`; queue is `database` with no worker documented. Nothing in `docs/operations/` covers installing on the shop's server, services for queue/scheduler, HTTPS, or go-live steps. | `docs/operations/` has backup, hardware, migration, multi-currency, health — no deployment. | Debug pages leak config/SQL to the counter; queued emails never send; no one knows the restore procedure. |

## 4. Important (P1) — do before or in the first week

| # | Finding |
|---|---------|
| P1-1 | **Scanner race at POS.** A barcode scanner types the code and sends Enter within ~50 ms; the search is debounced 200 ms, so Enter fires with an empty result list and nothing is added. Needs an exact-match lookup on Enter. |
| P1-2 | **POS has no keyboard shortcuts, no suspend/recall.** Spec 2.1 requires F-keys for tender/customer/suspend and a recall panel; today the till is mouse-driven. |
| P1-3 | **Branding says "SparesPro"** in both layouts' headers and every `<title>` although the company identity is captured once and shared. Should read Mokone Auto Spares. |
| P1-4 | **System Health has no persistence, history or operations checks** (queue failed jobs, scheduler heartbeat, backup age, disk space, debug-mode-in-production, log errors). The screen is live-only. |
| P1-5 | **Roles screen is a 310-row raw route list.** Usable, but default permission sets per role should be seeded so an admin starts from sensible templates and only adjusts. |
| P1-6 | **Demo logins are forced into password change** (`password_changed_at` null) — fine for the real admin, wrong for `DemoUsersSeeder`. |
| P1-7 | Supplier list column header "POS" (purchase orders) reads as "point of sale". |
| P1-8 | Duplicate `resources/js/Pages/Sales/POS/` and `Sales/Pos/` directories (identical files; invisible to Git on Windows, breaks on a Linux server). |
| P1-9 | `DemoDataSeeder` is not idempotent against `SupplierCatalogueSeeder` (duplicate `approved_suppliers` row aborts the run). |
| P1-10 | Brand asset images sit untracked in the repo root. |

## 5. Depth gaps against a best-in-class spares ERP (P2 — after go-live)

Scored per existing module: ✅ matches best practice · ◐ partial · ✗ missing.

| Module | Best-in-class expectation | SparesPro today | Gap |
|--------|---------------------------|-----------------|-----|
| **Inventory** | AVCO/FIFO ledger, bins, cycle counts, reorder, serial/batch, ABC/dead-stock, multi-branch | ✅ ledger/AVCO/bins/takes/reorder, ◐ serial/batch minimal, ✗ ABC/dead-stock, ◐ branch scoping (main branch only) | Per-user active branch; ABC & dead-stock reports |
| **Sales & POS** | <90 s scan-sell, shortcuts, suspend/recall, supervisor PIN, shift open/close with declared cash + variance lock, VAT-inclusive retail display, receipt reprint | ✅ scan/search, split tender, credit gates, cash-up (X/Z figures), ◐ Z-read does not lock a shift, ✗ shortcuts/suspend/PIN (fixed in Phase 8), ◐ inclusive-price display setting exists but the till shows exclusive | Shift locking; inclusive-price mode on the till |
| **Purchasing** | PO approval thresholds, GRN tolerance, 3-way match, returns, landed cost | ✅ all; ◐ landed cost (import shipments minimal) | Landed-cost allocation onto AVCO |
| **Workshop** | Job lifecycle, labour rates by make, parts issue at cost, invoicing split, warranty | ✅ all core; ◐ warranty minimal; ✗ job-card print | Job-card/quote print; promised-time alerts |
| **Customers** | Accounts, credit control, statements, groups/pricing, vehicles | ✅ all; ◐ statements by email run | Monthly statement run |
| **Suppliers** | Price-list import, approved list, performance | ✅ | — |
| **Finance** | GL, AR/AP, VAT201, TB/P&L/BS, bank rec, period close, audit trail | ✅ ledgers/reports/VAT; ◐ bank rec manual; ✗ audit trail (Phase 8); ◐ month-end close screen | Bank statement CSV import; month-end checklist screen |
| **Vehicle Reference** | Makes/models/engines, fitment, cross-refs, supersessions | ✅ | — |
| **Reports** | Dashboard, suites, export, scheduled | ✅ dashboard/suites, ◐ export (CSV on lists), ◐ scheduled (no runner) | Report-level CSV/PDF export; scheduler now exists for 9.8 |
| **System Admin** | RBAC enforced, audit log, config centre, health, backups, deployment preflight | ◐ config/health strong; ✗ RBAC enforcement, audit, backups, preflight (all Phase 8) | — |

---

## 6. What I would do better (and did, in Phase 8)

1. **Enforce the permission matrix in one middleware** and make the UI honest: cards, sub-cards and sidebar items only appear if the user may open them. Seed sensible per-role defaults so the admin edits, not builds.
2. **Make the supervisor override real**: credentials of a user with approval authority are checked server-side, the approver is stored on the invoice, and the override is written to the audit log.
3. **Add a single `audit_logs` stream** fed by a model trait (before/after diffs on master data, settings, users, roles) plus explicit events (overrides, period actions), with an admin screen to search it.
4. **Read the base currency once** (`CurrencyService::base()`), share it with every page, and let `MoneyDisplay` default to it. Remove the second currency field on Company.
5. **Give the system a heartbeat**: a scheduler with nightly health run, nightly database backup with retention, failed-job pruning and a heartbeat key; health checks for queue, scheduler, backup age, disk, debug mode; runs persisted so the screen shows history.
6. **Ship a preflight command and a deployment runbook** so go-live is a checklist, not folklore.
7. **Fix the till for a scanner**: exact-match on Enter, F-keys, suspend/recall.
