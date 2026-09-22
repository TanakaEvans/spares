# Implementation Plan

> The build order for SparesPro: which module/sub-module comes first, what follows, and **why**. Each phase's work items live in a tasks file under [tasks/](tasks/) — the tasks files are the living checklist; this plan is the map.

---

## Guiding Principles for the Order

1. **Master data before transactions.** You cannot sell a part that does not exist, into a branch that does not exist, at a price that does not exist.
2. **Stock in before stock out.** Purchasing/GRN before Sales — otherwise there is nothing to sell and no cost (AVCO) to compute margins with.
3. **The posting engines are built once, early, and everything uses them.** `StockLedgerService` and `GlPostingService` are Phase 0 infrastructure — retrofitting them later is the classic ERP disaster.
4. **Ship a sellable slice early.** After Phase 3 the business can actually trade (buy stock, sell at the counter, bank the money). Everything after that is depth.
5. **Existing System Admin module is extended, not rebuilt.**

---

## Phase 0 — Foundations (System Administration extensions + engines)

**Why first:** every other module reads these. Nothing here is user-visible glamour; all of it is load-bearing.

| Order | Work | Tasks file |
|-------|------|-----------|
| 0.1 | Configuration Centre ([spec](configuration-centre.md)) — settings registry, per-branch overrides | [tasks/00-foundations.md](tasks/00-foundations.md) |
| 0.2 | Currencies + exchange rates (10.12) — USD/ZWG/ZAR from day one | 〃 |
| 0.3 | Number sequences (10.9) — every document type needs one | 〃 |
| 0.4 | Company/branch document identity (logos, VAT no, banking on all printed docs) | 〃 |
| 0.5 | `StockLedgerService` + `GlPostingService` skeletons with tests | 〃 |
| 0.6 | Shared UI kit: `ModuleLayout` + sidebar config system, `DataTable`, `FormField`, `StatusBadge`, `SearchInput`, `MoneyDisplay`, `ConfirmDialog` | 〃 |
| 0.7 | Dashboard: replace school cards with the 10 module cards (permission-filtered) | 〃 |
| 0.8 | Print/PDF pipeline (DomPDF + one proof template) + email pipeline | 〃 |
| 0.9 | Page Guide system ([spec](design/page-guide.md)) — collapsible `F1` help on every page + in-app docs viewer | 〃 |
| 0.10 | Global search / command palette `Ctrl+K` ([spec](design/global-search-and-quick-actions.md)) | 〃 |
| 0.11 | Notifications Centre skeleton ([spec](modules/10-system-administration/10.13-notifications-centre.md)) — bell, routing matrix, queued channels | 〃 |

**Exit criteria:** a developer can scaffold any module page in under an hour using the shared kit; a test posts a balanced GL journal and a stock ledger entry.

---

## Phase 1 — Vehicle Reference + Inventory Master Data

**Why now:** parts are the heart of the system, and parts need vehicles (fitment) and categories before the first SKU is captured. Also unblocks the data-seeding work ([local-data-seeding.md](integrations/local-data-seeding.md)) which runs in parallel.

| Order | Sub-modules | Why this order |
|-------|-------------|----------------|
| 1.1 | 8.1 Makes, 8.2 Models & Variants, 8.5 Engine codes + seed packs | Fitment targets must exist before fitment records |
| 1.2 | 1.8 Categories, part brands, units of measure + seeds | Parts need categories at creation |
| 1.3 | 1.1 Parts Catalogue (CRUD, images, barcodes) | The core entity |
| 1.4 | 8.3 Cross-references, 8.6 Supersessions | Search correctness at POS depends on these |
| 1.5 | 1.6 Fitment Guide + 8.4 fitment lookup screen | The killer feature for counter accuracy |
| 1.6 | 1.3 Bin locations, 1.2 Stock Control read-side (levels view) | Physical warehouse mapping |
| 1.7 | Excel import wizards: parts, cross-refs, fitments | Bulk data entry — nobody types 10,000 parts |

Tasks: [tasks/01-inventory.md](tasks/01-inventory.md), [tasks/08-vehicle-reference.md](tasks/08-vehicle-reference.md)

---

## Phase 2 — Suppliers + Purchasing (stock inflow)

**Why now:** the first real stock must arrive through the front door (GRN) so AVCO costs are true from day one. Opening stock is loaded here too (as an opening-balance GRN or adjustment).

| Order | Sub-modules | Why |
|-------|-------------|-----|
| 2.1 | 6.1 Supplier profiles, 6.5 contacts | POs need suppliers |
| 2.2 | 6.2 Supplier price lists + import wizard | Cost prices; also the main new-part inflow |
| 2.3 | 3.2 Purchase Orders (skip 3.1 PRs for v1 — manual POs suffice) | Simplest path to ordering |
| 2.4 | 3.3 GRN + putaway + `StockLedgerService` PURCHASE_RECEIPT + AVCO | First stock movements |
| 2.5 | 1.2 Stock adjustments + transfers, 1.5 Reorder management | Now meaningful — there is stock |
| 2.6 | 3.4 Supplier invoices (3-way match), 6.3 approved suppliers | AP groundwork |
| 2.7 | 3.6 Returns to supplier, 3.5 supplier credit notes | Complete the loop |

Deferred to Phase 6: 3.1 requisitions, 3.7 import shipments, 3.8 price comparison, 6.4 performance.

Tasks: [tasks/03-purchasing.md](tasks/03-purchasing.md), [tasks/06-suppliers.md](tasks/06-suppliers.md)

---

## Phase 3 — Customers + Sales & POS (revenue — the go-live milestone)

**Why now:** with stock on the shelf at true cost, the business can trade. This phase ends with **go-live for counter sales**.

| Order | Sub-modules | Why |
|-------|-------------|-----|
| 3.1 | 5.1 Customer profiles, 5.7 groups, payment terms | Sales need customers (incl. Cash Customer) |
| 3.2 | 2.6 Price lists | Prices before selling |
| 3.3 | 2.1 **POS screen** + payments + thermal receipt | The single most important screen |
| 3.4 | 2.4 Tax invoices (posting: stock, revenue, VAT, COGS) | The legal + financial core of a sale |
| 3.5 | 2.5 Credit notes & returns | Day-2 reality at any counter |
| 3.6 | 5.2 Trade accounts + 5.3 credit management (limit checks at POS) | Trade customers are the big spenders |
| 3.7 | 2.2 Quotations → 2.3 Sales orders (reservations, back-orders) | Trade workflow |
| 3.8 | 1.4 Stock takes | Needed before/at go-live for opening accuracy |

Deferred to Phase 6: 2.7 promotions, 2.8 lay-bys, 2.9 delivery notes, 5.6 loyalty, 1.7 serial/batch.

Tasks: [tasks/02-sales-pos.md](tasks/02-sales-pos.md), [tasks/05-customers.md](tasks/05-customers.md)

---

## Phase 4 — Finance & Accounts

**Why now, not earlier:** the posting *engine* ran from Phase 0 (every invoice/GRN already journalised); Phase 4 builds the accountant's surface on top of an already-correct ledger. Doing the UI later, but the postings early, is what keeps the books clean.

| Order | Sub-modules |
|-------|-------------|
| 4.1 | 7.1 Chart of accounts (seeded) + 7.8 periods/years |
| 4.2 | 7.2 GL enquiry + manual journals |
| 4.3 | 7.3 AR receipts & allocations + 5.5 statements + ageing |
| 4.4 | 7.4 AP payments + payment run |
| 4.5 | 7.5 Bank accounts + statement import + reconciliation |
| 4.6 | 7.6 VAT returns |
| 4.7 | 7.7 Trial balance, P&L, balance sheet |
| 4.8 | Month-end close screen ([workflow](workflows/month-end-close.md)) |
| 4.9 | System Health dashboard assembly ([spec](operations/system-health.md)) — checks accumulate from Phase 0; the screen lands here, and gates go-live in Phase 7 |

Tasks: [tasks/07-finance.md](tasks/07-finance.md)

---

## Phase 5 — Workshop Management

**Why after Finance:** workshop invoicing rides on Sales + Finance; job costing rides on AVCO. Skip this phase entirely if the client has no workshop — nothing else depends on it.

| Order | Sub-modules |
|-------|-------------|
| 5.1 | 4.5 Vehicle registry (extends customer vehicles from Phase 3) |
| 5.2 | 4.2 Labour codes & rates, 4.3 technicians |
| 5.3 | 4.1 Job cards (full lifecycle) + technician board |
| 5.4 | 4.4 Parts requisition to jobs |
| 5.5 | 4.8 Workshop invoicing + 4.6 job costing |
| 5.6 | 4.7 Warranty claims |

Tasks: [tasks/04-workshop.md](tasks/04-workshop.md)

---

## Phase 6 — Reports, Analytics & Deferred Depth

| Order | Work |
|-------|------|
| 6.1 | 9.1 Executive dashboard (now that real data flows) |
| 6.2 | 9.2–9.7 report suites (sales → inventory → financial → the rest) |
| 6.3 | 9.8 Scheduled reports + email delivery |
| 6.4 | Deferred items: promotions, lay-bys, loyalty, requisitions, import shipments, serial/batch tracking, supplier performance, price comparison, technical bulletins, communication log |

Tasks: [tasks/09-reports.md](tasks/09-reports.md) + deferred sections in each module's tasks file.

---

## Phase 7 — Go-Live Hardening & Migration

| Work | Doc |
|------|-----|
| Data migration: opening stock, customer/supplier balances, opening trial balance | [operations/data-migration.md](operations/data-migration.md) |
| Hardware setup: thermal printers, scanners, drawer, UPS | [operations/hardware-and-printing.md](operations/hardware-and-printing.md) |
| Backups + restore drill | [operations/backup-and-resilience.md](operations/backup-and-resilience.md) |
| Role setup + user training + permission audit | Module 10 docs |
| Full regression pass | [testing-strategy.md](testing-strategy.md) |

---

## The Plan ↔ Tasks ↔ Docs Relationship

```
implementation-plan.md        (this file — the WHY and the ORDER)
        │  each phase points to…
        ▼
docs/tasks/NN-*.md            (the WHAT — living checklists, updated as work completes)
        │  each task links to…
        ▼
docs/modules/**/*.md          (the HOW — specs: fields, rules, screens, edge cases)
        │  verified by…
        ▼
testing-strategy.md           (the PROOF — a task is only [x] when its tests pass
                               AND the feature was functionally exercised)
```

**Rule (also in AGENTS.md):** when a task is finished — code written, automated tests green, feature manually exercised per the test strategy — update its checkbox to `[x]` in the tasks file *in the same commit*. A phase is complete when its tasks files show all non-deferred items done.
