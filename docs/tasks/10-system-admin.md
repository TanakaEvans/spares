# Tasks: System Administration (extensions)

> Spans Phase 0 → Phase 7 of the [implementation plan](../implementation-plan.md). Spec: [module doc](../modules/10-system-administration.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

Module 10 already exists (users, roles, company, branches, departments, activity logs, backup, password policy — 10.1–10.4, 10.6–10.8). This file tracks only the **extensions**. Several extensions are Phase 0 foundations and live in the foundations checklist — do not duplicate them here.

## Built in Phase 0 — see [tasks/00-foundations.md](00-foundations.md)

| Sub-module | Note |
|-----------|------|
| 10.5 System Settings extension · [spec](../modules/10-system-administration/10.5-system-settings.md) | Configuration Centre: settings registry + per-branch overrides (plan item 0.1) — built in Phase 0 — see tasks/00-foundations.md |
| 10.9 Number Sequences · [spec](../modules/10-system-administration/10.9-number-sequences.md) | Every document type needs one (plan item 0.3) — built in Phase 0 — see tasks/00-foundations.md |
| 10.10 Print Templates · [spec](../modules/10-system-administration/10.10-print-templates.md) | Print/PDF pipeline + proof template (plan item 0.8) — built in Phase 0 — see tasks/00-foundations.md |
| 10.11 Email Configuration · [spec](../modules/10-system-administration/10.11-email-configuration.md) | Email pipeline (plan item 0.8) — built in Phase 0 — see tasks/00-foundations.md |
| 10.12 Currencies + exchange rates · [spec](../modules/10-system-administration/10.12-currencies.md) | USD/ZWG/ZAR from day one (plan item 0.2) — built in Phase 0 — see tasks/00-foundations.md |

Company/branch document identity (logos, VAT number, banking details on printed docs) is also Phase 0 (plan item 0.4).

## Module & Permission Registration (incremental — every phase)  ·  [spec](../modules/10-system-administration/10.1-users-roles.md)

Repeated for each new module as it is built (Phases 1–6). Each module's own tasks file carries its "SystemRoute permission seeds" task; the items below are the shared plumbing.

### Backend
- [ ] `SystemModule` seeder entries for the 9 new modules (Inventory, Sales & POS, Purchasing, Workshop, Customers, Suppliers, Finance, Vehicle Reference, Reports)
- [ ] `SystemRoute` seeder pattern/helper so each module registers its routes consistently (`module.resource.*` naming)
- [ ] Dashboard module cards wired to the seeded modules, permission-filtered (Phase 0 item 0.7 provides the card grid — this keeps it current as modules land)
### Tests
- [ ] Feature test: user with no routes for a module sees no card and gets 403 on its routes
- [ ] Functional pass: log in as a restricted user, confirm only permitted module cards render

## Default Role Seeds for the Spares Business  ·  [spec](../modules/10-system-administration/10.1-users-roles.md)

Best seeded per phase as the routes they reference come into existence; complete by Phase 7.

### Backend
- [ ] Role seeder for the 12 suggested roles: Super Admin, Branch Manager, Sales Manager, Cashier / Counter Staff, Purchasing Officer (Buyer), Warehouse Staff, Accounts Clerk, Accountant, Workshop Foreman, Technician, Receptionist, Reports Only
- [ ] Role → SystemRoute mappings per the module-access matrix in the spec (e.g. Branch Manager: all modules own branch, view-only financials; Technician: own jobs only)
- [ ] Suggested department seeds: Parts Sales, Workshop / Service, Purchasing / Procurement, Finance & Accounts, Warehouse / Stores, Management
### Tests
- [ ] Feature tests: Cashier can reach POS + quotations but not finance routes; Accounts Clerk reaches AR/AP but not role management
- [ ] Functional pass: sign in as seeded Cashier and Accountant, walk their permitted screens

## Branch Extensions  ·  [spec](../modules/10-system-administration/10.3-branch-management.md)

Needed as their consuming features land (workshop flag by Phase 5, price list default by Phase 3).

### Backend
- [ ] Migration: extend `branches` with branch_code, address, phone, email, is_workshop, is_warehouse, is_head_office, default_price_list_id, allow_negative_stock, gl_profit_centre_id
- [ ] Consume flags: `is_workshop` gates workshop module per branch; `allow_negative_stock` read by StockLedgerService; `default_price_list_id` read at POS
### Frontend
- [ ] Extend existing branch form (`admin.branches.index`) with the new fields
### Tests
- [ ] Feature tests: negative-stock issue blocked/allowed per branch flag; non-workshop branch hides workshop nav
- [ ] Functional pass: toggle is_workshop off and confirm workshop screens vanish for that branch

## System Health & Integrity Dashboard (Phase 4 assembly)  ·  [spec](../operations/system-health.md)

### Backend
- [ ] `HealthCheck` interface + discovery runner + `health_check_runs`/`health_check_results` persistence + nightly schedule + run-after-outage hook
- [ ] Core checks: journals balanced, AR/AP = control, stock levels = ledger sums, gapless sequences, queue/failed jobs, scheduler heartbeat, backup age, disk, today's exchange rate, stale drafts, stale reservations
- [ ] Red-check → mandatory Notifications Centre event; routes (`admin.health`) + permission seeds
### Frontend
- [ ] Health screen: grouped ✓/⚠/✗ rows, every issue deep-linked, per-branch section, "Run all checks now", history per check; health dot on System Admin card/top bar
### Tests
- [ ] Feature tests: seeded imbalance turns check red + fires notification; discovery picks up a module-registered check
- [ ] Functional pass: force a failed job and a stale draft, watch both go amber, click through, resolve, re-run to green

## Legacy Layout Migration

- [ ] Migrate pre-existing AdminLayout pages (Company, Branches, Departments, Sections, Employees, Users, Roles, Auth Management) onto the shared `ModuleLayout` + `nav/system-admin.js` config; delete `AdminLayout.jsx` and its school-era branding when the last page moves
- [ ] Functional pass: every System Admin screen shows the same slate-900 sidebar and breadcrumb shell

## Permission Audit (Phase 7 — go-live hardening)

### Backend
- [ ] Permission audit report: roles × routes matrix export; orphan routes (seeded but unassigned); users with Super Admin
- [ ] Activity-log review pass for admin actions (role changes, setting changes) ahead of go-live
### Tests
- [ ] Functional pass: run the audit on the production role set, resolve every orphan route and over-broad grant before go-live sign-off

## Deferred (Phase 6+)
- [ ] Nothing module-specific deferred here — 10.6–10.8 (activity logs, backup, password policy) remain as-is; go-live backup/restore drill tracked in [operations/backup-and-resilience.md](../operations/backup-and-resilience.md)
