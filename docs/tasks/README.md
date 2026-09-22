# Tasks Index

The living checklists for building SparesPro. The [implementation plan](../implementation-plan.md) sets the order and the why; each file below holds the what.

| Tasks file | Module | Phase | Link |
|-----------|--------|-------|------|
| 00-foundations.md | System Administration extensions + posting engines + shared UI kit | 0 | [tasks/00-foundations.md](00-foundations.md) |
| 01-inventory.md | Module 1 — Inventory Management | 1 (stock control depth in 2, stock takes in 3) | [tasks/01-inventory.md](01-inventory.md) |
| 02-sales-pos.md | Module 2 — Sales & POS | 3 | [tasks/02-sales-pos.md](02-sales-pos.md) |
| 03-purchasing.md | Module 3 — Purchasing & Procurement | 2 | [tasks/03-purchasing.md](03-purchasing.md) |
| 04-workshop.md | Module 4 — Workshop Management | 5 | [tasks/04-workshop.md](04-workshop.md) |
| 05-customers.md | Module 5 — Customer Management | 3 | [tasks/05-customers.md](05-customers.md) |
| 06-suppliers.md | Module 6 — Supplier Management | 2 | [tasks/06-suppliers.md](06-suppliers.md) |
| 07-finance.md | Module 7 — Finance & Accounts | 4 | [tasks/07-finance.md](07-finance.md) |
| 08-vehicle-reference.md | Module 8 — Vehicle & Parts Reference | 1 (technical bulletins in 6) | [tasks/08-vehicle-reference.md](08-vehicle-reference.md) |
| 09-reports.md | Module 9 — Reports & Analytics | 6 | [tasks/09-reports.md](09-reports.md) |
| 10-system-admin.md | Module 10 — System Administration (extensions) | 0 → 7 (incremental) | [tasks/10-system-admin.md](10-system-admin.md) |

Each module also carries a **Deferred (Phase 6)** section for items the plan pushes out (promotions, lay-bys, loyalty, requisitions, import shipments, serial/batch, supplier performance, price comparison, technical bulletins, communication log).

## How to use

- Checkbox legend: `[ ]` todo · `[~]` in progress · `[x]` done.
- A task is only `[x]` when **code is written, automated tests pass, AND the feature was functionally exercised** per the [testing strategy](../testing-strategy.md).
- Update the checkbox **in the same commit** as the completed work — the checklist and the code never drift apart.
- A phase is complete when its tasks files show all non-deferred items done.

```
implementation-plan.md   — the WHY and the ORDER
        ▼
tasks/NN-*.md            — the WHAT (these checklists)
        ▼
modules/**/*.md          — the HOW (specs: fields, rules, screens)
        ▼
testing-strategy.md      — the PROOF (tests green + functional pass)
```
