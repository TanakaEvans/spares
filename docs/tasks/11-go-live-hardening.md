# Tasks — Phase 8: Pre-Deployment Hardening

> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.
>
> Source: [audit/2026-10-20-pre-deployment-audit.md](../audit/2026-10-20-pre-deployment-audit.md). Order below = execution order (blockers first).

---

## 8.1 Access control — enforce the permission matrix (P0-1..3)

### Backend
- [x] `EnforceRoutePermission` middleware (appended to the `web` group): superuser bypass; routes in `system_routes` require `User::hasRouteAccess()`; core routes exempt (`dashboard`, `modules.show`, `password.*`, `logout`, `search.global`, `help.*`, `notifications.*`); Inertia `Auth/Unauthorized` page on refusal — done 2026-10-20
- [x] `AdminMiddleware` honours route grants (a Manager granted `admin.health` may open it) — done 2026-10-20
- [x] `RolePermissionSeeder` — default route sets per seeded role (Admin, Manager, Cashier, Buyer, Accountant, Workshop, Inventory Clerk) by route-name pattern; additive + idempotent; wired into `DatabaseSeeder` — done 2026-10-20
- [x] Share `auth.routes` (accessible route names, `*` for superuser) via `HandleInertiaRequests` — done 2026-10-20
### Frontend
- [x] `can(routeName)` helper (`resources/js/lib/can.js`); Dashboard module cards, sub-module cards and `ModuleLayout` sidebar items filtered by it — done 2026-10-20
### Tests
- [x] `RoutePermissionTest`: cashier opens POS (200), cannot open manual journals (403), superuser can; dashboard always reachable; role defaults seeded — done 2026-10-20
### Functional pass
- [x] Logged in as demo cashier: only Sales/Customers cards shown; `/finance/journals` → Unauthorized page — done 2026-10-20

## 8.2 Base currency & branding everywhere (P0-6, P1-3)

- [x] Share `currency` `{code, symbol, decimals}` from `CurrencyService::base()`; `MoneyDisplay` defaults to it — done 2026-10-20
- [x] Settings label "per $1 spent" → currency-neutral; Company screen currency field replaced by a read-only pointer to the Configuration Centre base currency — done 2026-10-20
- [x] Layout headers + `<title>` suffix use the company trading name — done 2026-10-20
- [x] Functional pass: POS/Stock/Suppliers/Dashboard show `R` — done 2026-10-20

## 8.3 POS control & speed (P0-4, P1-1, P1-2)

- [x] Real supervisor override: tender dialog asks for approver username + password when a line exceeds the limit; server verifies credentials + authority (`sales.pos.approve-discount` route grant or Admin/Superuser), stores `discount_approved_by` on the invoice, writes an audit entry — done 2026-10-20
- [x] Scanner-safe Enter: exact part-number/barcode match adds the line immediately (no debounce race) — done 2026-10-20
- [x] Keyboard shortcuts: `F2` customer, `F4` tender, `F8` suspend, `F9` recall, `Esc` close; shown in the page guide — done 2026-10-20
- [x] Suspend/recall (per-till, browser-local; recall re-prices through the server lookup) — done 2026-10-20
- [x] Tests: override rejected without approver, accepted with a Manager's credentials and recorded — done 2026-10-20
- [ ] Deferred: shift open/close with declared float + Z-read lock (`sales.pos.shift`); VAT-inclusive display mode on the till

## 8.4 Audit trail (P0-5)

- [x] Migration `audit_logs` (user, branch, action, auditable type/id, before/after JSON, note, IP); `AuditLog` model; `AuditLogService::record()` — done 2026-10-20
- [x] `Auditable` trait (updated/deleted diffs) on Customer, Supplier, Part, PriceList, PriceListItem, User, Role, Branch, Company, Setting — done 2026-10-20
- [x] Explicit events: settings change, role route sync, POS discount override, user lock/unlock — done 2026-10-20
- [x] Screen `admin.audit-log` (filters: user, action, entity, date) + sidebar entry + page guide — done 2026-10-20
- [x] Tests: a price edit records before/after; override records approver — done 2026-10-20

## 8.5 Operations: scheduler, backups, health (P0-7, P1-4)

- [x] `routes/console.php` schedule: health run nightly, DB backup nightly, failed-job prune weekly, scheduler heartbeat every minute — done 2026-10-20
- [x] `backup:run` command + `BackupService` (mysqldump when available, pure-PHP dump fallback, gzip, retention from `backup.retention_days`); Backups screen lists real dumps with download — done 2026-10-20
- [x] `health:run` command persisting to `health_check_runs`; failure → notification event `system.health_failed` to admins — done 2026-10-20
- [x] Operations checks: queue failed jobs, scheduler heartbeat age, last backup age, disk free, debug mode in production, log errors (24 h) — done 2026-10-20
- [x] Health screen shows last scheduled run + history — done 2026-10-20
- [x] Tests: backup file produced (sqlite fallback), health run persisted, ops checks flag a stale heartbeat — done 2026-10-20

## 8.6 Security policy enforcement (P0-8)

- [x] Login lockout uses `security.max_failed_attempts` / `security.lockout_minutes` (temporary lock, auto-expiry); password change validates `security.password_*` and sets expiry from `security.password_expiry_days` — done 2026-10-20
- [x] Tests: lockout after N attempts, auto-unlock after the window, weak password rejected per policy — done 2026-10-20

## 8.7 Deployment runbook & preflight (P0-9)

- [x] `app:preflight` command — production readiness checklist (env, debug, key, debugbar, HTTPS URL, queue/scheduler heartbeats, storage, mysqldump, company VAT no., base currency, default admin password changed) — done 2026-10-20
- [x] `docs/operations/deployment.md` — server install, `.env` template, services (queue worker + scheduler on Windows and Linux), HTTPS, printers, backup/restore drill, go-live day checklist, rollback — done 2026-10-20

## 8.8 Housekeeping (P1-5..10)

- [x] `DemoUsersSeeder` sets `password_changed_at`; `DemoDataSeeder` approved-supplier insert idempotent — done 2026-10-20
- [x] Supplier list "POS" header → "Open POs"; duplicate `Sales/POS` page directory removed; brand images moved to `docs/branding/` — done 2026-10-20
- [x] Docs: implementation plan Phase 8, AGENTS.md current state, page guides for new screens — done 2026-10-20

## 8.9 Deferred depth (post go-live, from audit §5)

- [ ] Per-user active branch switching (till trades on the main branch)
- [ ] ABC analysis + dead-stock report
- [ ] Shift locking (Z-read) and VAT-inclusive till display
- [ ] Landed-cost allocation onto AVCO for import shipments
- [ ] Workshop job-card print template; promised-time alerts
- [ ] Monthly statement email run; bank statement CSV import
- [ ] Report-level CSV/PDF export; scheduled report runner on the new scheduler
