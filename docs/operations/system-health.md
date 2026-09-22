# System Health & Integrity Dashboard ★

> The special sauce: one admin screen that proves, at a glance, that the whole ERP is healthy — books balanced, stock true, queues flowing, backups fresh, printers alive. Most ERPs make you *discover* problems; SparesPro **announces** them. Route: `admin.health` (System Admin › Governance).

---

## The screen

```
┌────────────────────────────────────────────────────────────────────┐
│ System Health                            Last full check: 06:00 ✓  │
│                                          [Run all checks now]      │
├──────────────── FINANCIAL INTEGRITY ───────────────────────────────┤
│ ✓ Journals balanced (all 14,203)      ✓ AR ledger = Debtors ctrl   │
│ ✓ AP ledger = Creditors ctrl          ✓ Stock value = Inventory GL │
│ ✓ Invoice sequence gapless            ✓ VAT control reconciles     │
├──────────────── STOCK INTEGRITY ───────────────────────────────────┤
│ ✓ Stock levels = ledger sums (2,847 parts × 2 branches)            │
│ ⚠ 3 parts with stale reservations > 30 days        [view]         │
├──────────────── OPERATIONS ────────────────────────────────────────┤
│ ✓ Queue: 0 pending, 0 failed          ✓ Scheduler ran 5 min ago    │
│ ✓ Last backup: 22:00 yesterday (verified)   ✓ Off-site copy: 22:41 │
│ ⚠ Disk: 78% used (warn at 75%)        ✓ Error log: 0 today        │
│ ✓ Exchange rate captured today        ✓ Open periods: Sep 2026     │
├──────────────── PER BRANCH ────────────────────────────────────────┤
│ Harare Main   ✓ till closed y'day  ✓ printer ok   ⚠ 1 GRN draft>7d│
│ Bulawayo      ✓                    ✗ printer unreachable  [view]   │
└────────────────────────────────────────────────────────────────────┘
```

Every ⚠/✗ links to the exact records ([UI-13](../design/ui-rules.md)) and states the fixing action.

## Check catalogue

| Group | Check | Source / rule |
|-------|-------|---------------|
| Financial | Every posted journal balances | [debugging-standards.md §6](../coding-standards/debugging-standards.md) |
| | AR/AP sub-ledger = control account | [month-end-close.md §②](../workflows/month-end-close.md) — but checked nightly, not monthly |
| | Stock value report = Inventory GL | 〃 |
| | Gapless document sequences | Per sequence key |
| Stock | `stock_levels` cache = `stock_ledger` sums | Nightly full, on-demand per part |
| | Stale reservations, negative stock (where disallowed), orphan bin quantities | Housekeeping sweeps |
| Operations | Queue depth & failed jobs (retry button) | `failed_jobs` |
| | Scheduler heartbeat, backup age + restore-verification age, off-site copy age | [backup-and-resilience.md](backup-and-resilience.md) |
| | Disk space, log error/critical counts (24 h) | Server + log channel |
| | Today's exchange rate present | [multi-currency.md](multi-currency.md) |
| Hygiene | Documents stuck in draft > N days (GRNs, invoices, takes) — N per type in Configuration Centre | Prevents forgotten half-work |
| Per branch | Till closed daily, print agent reachable, stock-take freshness (A-class > 30 d overdue) | |

## Behaviour

1. **Nightly full run** (scheduled job) + **on-demand** "Run all checks" + automatic run **after any outage recovery** and as a **gate in data-migration go-live** ([data-migration.md](data-migration.md)) and month-end close step ②.
2. Results persist (`health_check_runs` / `health_check_results`) so the screen loads instantly and shows history/trend per check.
3. A check turning red fires a Notifications Centre event ([10.13](../modules/10-system-administration/10.13-notifications-centre.md)) — mandatory, unmutable, routed to administrators. Red financial-integrity = P1 by definition.
4. Checks are **registered like settings/events**: each module declares its checks in code (`HealthCheck` interface: `key`, `group`, `run(): Result`), the runner discovers them — adding a module adds its checks with zero central edits.
5. The dashboard header shows a compact health dot (green/amber/red) in the System Admin module card and top bar for super admins — trouble is visible before anyone opens the screen.

## Why this is special

It converts the ERP's biggest fear — *"are the numbers right?"* — into a green row you can show an owner or auditor every morning. It also closes the loop on everything else in these docs: the invariants from debugging standards, the reconciliations from month-end, the backup discipline, the migration go/no-go, and the notification routing all surface in one place.

## Build

- `HealthCheck` interface + runner + persistence + screen: [tasks/10-system-admin.md](../tasks/10-system-admin.md); financial/stock checks land with their services (Phases 0–4); the screen assembles in Phase 4 and gates go-live in Phase 7 ([implementation-plan.md](../implementation-plan.md)).
