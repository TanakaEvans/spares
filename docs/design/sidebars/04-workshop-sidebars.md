# Sub-module Sidebars: Workshop Management

Sub-module sidebars for [Module 4: Workshop Management](../../modules/04-workshop-management.md), built to the [universal template](README.md).

## 4.1 Job Cards

> Context: entered via Workshop › Job Cards. [Spec](../../modules/04-workshop-management/4.1-job-cards.md)

```
↰ Workshop
▍ JOB CARDS  (wrench)
──────────────────────────────
WORK
  Job Board ............... workshop.board                  (kanban-square) [badge: open jobs]
  All Job Cards ........... workshop.jobs.index             (list)
  New Job / Intake ........ workshop.jobs.create            (plus-circle)
INSIGHTS
  Overdue Jobs ............ workshop.jobs.overdue           (hourglass)     [badge: overdue]
QUICK LINKS ⇄
  Vehicle Registry ........ workshop.vehicles.search        (car-front)
  Parts Requisition ....... workshop.parts.queue            (package-search)
  Workshop Invoicing ...... workshop.jobs.invoice           (receipt)
  Warranty Claims ......... workshop.warranty.index         (shield-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- `open jobs` counts cards not yet `closed`; `overdue` counts jobs past `promised_at` and not yet in `quality_check` (the promised-time alarm feed).
- Job card detail (`workshop.jobs.show`) is reached from the board/list, never from the sidebar; its Costing tab is foreman/manager only.
- Technicians see the board filtered to their own allocation.

## 4.2 Labour Management

> Context: entered via Workshop › Labour Management. [Spec](../../modules/04-workshop-management/4.2-labour-management.md)

```
↰ Workshop
▍ LABOUR MANAGEMENT  (timer)
──────────────────────────────
WORK
  Labour Codes ............ workshop.labour.index           (list)
  New Labour Code ......... workshop.labour.create          (plus-circle)
SETUP
  Labour Rates ............ workshop.labour.rates           (banknote)
QUICK LINKS ⇄
  Job Cards ............... workshop.jobs.index             (wrench)
  Technicians ............. workshop.technicians.index      (users)
  Job Costing ............. workshop.costing.reports        (calculator)
  Warranty Claims ......... workshop.warranty.index         (shield-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Labour Rates (named rates by customer type and branch) is permission-gated to workshop manager; SETUP hides entirely for other roles.
- Code detail (`workshop.labour.show`) with book times and make overrides opens from the codes list.

## 4.3 Technician Management

> Context: entered via Workshop › Technicians. [Spec](../../modules/04-workshop-management/4.3-technician-management.md)

```
↰ Workshop
▍ TECHNICIANS  (users)
──────────────────────────────
WORK
  Technician Board ........ workshop.board                  (kanban-square)
  All Technicians ......... workshop.technicians.index      (list)
  Clock Terminal .......... workshop.clock                  (clock)
QUICK LINKS ⇄
  Job Cards ............... workshop.jobs.index             (wrench)
  Labour Codes ............ workshop.labour.index           (timer)
  Job Costing ............. workshop.costing.reports        (calculator)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Clock Terminal is a touch-first operational screen: sidebar auto-collapses to icons (hover/`[` re-expands).
- Technician KPI dashboards and time-log history render on technician detail (`workshop.technicians.show`), opened from the list.
- The board is shared with 4.1; allocation actions on it are foreman-only.

## 4.4 Parts Requisition

> Context: entered via Workshop › Parts Requisition. [Spec](../../modules/04-workshop-management/4.4-parts-requisition.md)

```
↰ Workshop
▍ PARTS REQUISITION  (package-search)
──────────────────────────────
WORK
  Pick Queue .............. workshop.parts.queue            (list-ordered)  [badge: open requests]
  Issue Station ........... workshop.parts.issue            (scan-barcode)
QUICK LINKS ⇄
  Job Cards ............... workshop.jobs.index             (wrench)
  Job Costing ............. workshop.costing.reports        (calculator)
  Warranty Claims ......... workshop.warranty.index         (shield-check)
  Purchasing: Requisitions  purchasing.requisitions.index   (shopping-cart)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Issue Station is scan-driven and keyboard/scanner-first: sidebar auto-collapses to icons.
- `open requests` is the storeman's live count of unissued requisition lines.
- Requesting and returning parts happens on the job card's Parts tab (`workshop.jobs.show`), not from this sidebar; out-of-stock requests auto-raise a purchase requisition (Module 3.1).

## 4.5 Vehicle Registry

> Context: entered via Workshop › Vehicle Registry. [Spec](../../modules/04-workshop-management/4.5-vehicle-registry.md)

```
↰ Workshop
▍ VEHICLE REGISTRY  (car-front)
──────────────────────────────
WORK
  Vehicle Lookup .......... workshop.vehicles.search        (search)
  New Vehicle ............. workshop.vehicles.create        (plus-circle)
QUICK LINKS ⇄
  Job Cards ............... workshop.jobs.index             (wrench)
  Parts Requisition ....... workshop.parts.queue            (package-search)
  Warranty Claims ......... workshop.warranty.index         (shield-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Lookup is plate/VIN/customer search with quick-create; vehicle detail & history (`workshop.vehicles.show`) opens from results.
- The embedded vehicle panel inside `workshop.jobs.create` reuses these screens inline and never swaps the sidebar out of Job Cards context.

## 4.6 Job Costing

> Context: entered via Workshop › Job Costing. [Spec](../../modules/04-workshop-management/4.6-job-costing.md)

```
↰ Workshop
▍ JOB COSTING  (calculator)
──────────────────────────────
WORK
  WIP Valuation ........... workshop.costing.wip            (layers)
INSIGHTS
  Margin Reports .......... workshop.costing.reports        (trending-up)
QUICK LINKS ⇄
  Job Cards ............... workshop.jobs.index             (wrench)
  Workshop Invoicing ...... workshop.jobs.invoice           (receipt)
  Labour Codes ............ workshop.labour.index           (timer)
  Warranty Claims ......... workshop.warranty.index         (shield-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The live per-job costing view is the Costing tab on the job card (`workshop.jobs.show`), foreman/manager only; this sidebar carries the aggregate views.
- WIP Valuation is the month-end open-job cost total consumed by finance period close.

## 4.7 Warranty Claims

> Context: entered via Workshop › Warranty Claims. [Spec](../../modules/04-workshop-management/4.7-warranty-claims.md)

```
↰ Workshop
▍ WARRANTY CLAIMS  (shield-check)
──────────────────────────────
WORK
  All Claims .............. workshop.warranty.index         (list)          [badge: open claims]
  New Claim ............... workshop.warranty.create        (plus-circle)
QUICK LINKS ⇄
  Job Cards ............... workshop.jobs.index             (wrench)
  Purch: Returns to Supp .. purchasing.returns.index        (undo-2)
  Purch: Credit Notes ..... purchasing.credit-notes.index   (file-minus)
  Job Costing ............. workshop.costing.reports        (calculator)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- `open claims` counts pipeline claims not yet settled; the list carries status/aging/supplier filters.
- New Claim is created from a warranty job card's flagged lines; claim detail links the return to supplier (3.6) and settlement credit note (3.5).

## 4.8 Workshop Invoicing

> Context: entered via Workshop › Invoicing. [Spec](../../modules/04-workshop-management/4.8-workshop-invoicing.md)

```
↰ Workshop
▍ WORKSHOP INVOICING  (receipt)
──────────────────────────────
WORK
  Ready to Invoice ........ workshop.jobs.index?status=completed  (list-checks)  [badge: awaiting invoice]
  Pre-invoice Review ...... workshop.jobs.invoice           (file-check)
QUICK LINKS ⇄
  Job Cards ............... workshop.jobs.index             (wrench)
  Sales: Invoices ......... sales.invoices.index            (receipt)
  Job Costing ............. workshop.costing.reports        (calculator)
  Vehicle Registry ........ workshop.vehicles.search        (car-front)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- `awaiting invoice` counts completed jobs not yet invoiced, aged.
- The generated tax invoice itself lives in Sales (`sales.invoices.show`); generation snapshots costing (4.6), excludes warranty lines (4.7) and runs the credit check.
- Credit-hold customers block at invoice; only service advisors and managers see this sidebar.
