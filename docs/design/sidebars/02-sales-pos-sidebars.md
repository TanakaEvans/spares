# Sub-module Sidebars: Sales & POS

Per-sub-module sidebar layouts for [Module 2: Sales & POS](../../modules/02-sales-pos.md), following the universal template and hard rules in [README.md](README.md).

## 2.1 Counter Sales (POS)

> Context: entered via Sales › Counter Sales (POS). [Spec](../../modules/02-sales-pos/2.1-counter-sales-pos.md)

```
↰ Sales
▍ COUNTER SALES  (scan-line)
──────────────────────────────
WORK
  POS Terminal ............ sales.pos                      (scan-line)
  Suspended Sales ......... sales.pos (recall panel)       (pause-circle)  [badge: suspended]
  Shift & X/Z Reads ....... sales.pos.shift                (clock)
QUICK LINKS ⇄
  Tax Invoices ............ sales.invoices.index           (receipt)
  Credit Notes & Returns .. sales.credits.index            (rotate-ccw)
  Lay-bys ................. sales.laybys.index             (calendar-clock)
  Promotions .............. sales.promotions.index         (percent)
  Price Lists ............. sales.pricelists.index         (tags)
  Inventory: Fitment ...... inventory.fitments.vehicle     (car)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- POS is an operational screen: the sidebar auto-collapses to icons to maximise selling space (hover/`[` re-expands).
- Badge: suspended carts on this terminal/shift (they auto-expire at Z-read and are listed on the shift report).
- Shift screen actions are role-split: X-read for senior cashiers, Z-read/void/no-sale for supervisors and above.

## 2.2 Quotations

> Context: entered via Sales › Quotations. [Spec](../../modules/02-sales-pos/2.2-quotations.md)

```
↰ Sales
▍ QUOTATIONS  (file-text)
──────────────────────────────
WORK
  All Quotations .......... sales.quotes.index             (list)          [badge: expiring soon]
  New Quote ............... sales.quotes.create            (plus-circle)
QUICK LINKS ⇄
  Sales Orders ............ sales.orders.index             (clipboard-list)
  Tax Invoices ............ sales.invoices.index           (receipt)
  Price Lists ............. sales.pricelists.index         (tags)
  Promotions .............. sales.promotions.index         (percent)
  Counter Sales (POS) ..... sales.pos                      (scan-line)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: issued quotes within 5 days of expiry above the reminder value threshold (the nightly sweep flips lapsed ones to `expired`).
- Revisions, conversion, and win/loss closure all run from quote detail (`sales.quotes.show`); conversion targets are one click away via Quick Links.

## 2.3 Sales Orders

> Context: entered via Sales › Sales Orders. [Spec](../../modules/02-sales-pos/2.3-sales-orders.md)

```
↰ Sales
▍ SALES ORDERS  (clipboard-list)
──────────────────────────────
WORK
  All Orders .............. sales.orders.index             (list)
  New Order ............... sales.orders.create            (plus-circle)
INSIGHTS
  Back-order Report ....... sales.orders.backorders        (package-x)     [badge: open back-orders]
QUICK LINKS ⇄
  Quotations .............. sales.quotes.index             (file-text)
  Tax Invoices ............ sales.invoices.index           (receipt)
  Delivery Notes .......... sales.deliveries.index         (truck)
  Inventory: Stock ........ inventory.stock.index          (layers)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: open `is_back_order` lines awaiting supplier stock (allocation is oldest-order-first on GRN receipt).
- Pick, pack, dispatch, and invoice actions run from the order detail status stepper; the list carries pipeline, back-order, and overdue filters.

## 2.4 Tax Invoices

> Context: entered via Sales › Tax Invoices. [Spec](../../modules/02-sales-pos/2.4-tax-invoices.md)

```
↰ Sales
▍ TAX INVOICES  (receipt)
──────────────────────────────
WORK
  All Invoices ............ sales.invoices.index           (list)
  Batch Invoicing ......... sales.invoices.batch           (layers)
QUICK LINKS ⇄
  Counter Sales (POS) ..... sales.pos                      (scan-line)
  Credit Notes & Returns .. sales.credits.index            (rotate-ccw)
  Sales Orders ............ sales.orders.index             (clipboard-list)
  Delivery Notes .......... sales.deliveries.index         (truck)
  Price Lists ............. sales.pricelists.index         (tags)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- No create item: invoices are born posted at POS or drafted from fulfilled orders/proformas — there is no free-standing "new invoice" entry point for cashiers; manual invoices are a supervisor action from the list.
- Batch Invoicing is Sales Supervisor and above; GL views and period locks belong to Finance roles.

## 2.5 Credit Notes & Returns

> Context: entered via Sales › Credit Notes & Returns. [Spec](../../modules/02-sales-pos/2.5-credit-notes-returns.md)

```
↰ Sales
▍ CREDITS & RETURNS  (rotate-ccw)
──────────────────────────────
WORK
  All Credit Notes ........ sales.credits.index            (list)
  New Credit Note ......... sales.credits.create           (plus-circle)
QUICK LINKS ⇄
  Tax Invoices ............ sales.invoices.index           (receipt)
  Counter Sales (POS) ..... sales.pos                      (scan-line)
  Delivery Notes .......... sales.deliveries.index         (truck)
  Inventory: Serials ...... inventory.serials.index        (scan-barcode)
  Inventory: Fitment ...... inventory.fitments.review      (flag)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- New Credit Note opens the invoice-lookup wizard — every credit must reference an original invoice (no standalone credits).
- Cash-refund and over-limit approvals prompt inline per the settlement rules; `does_not_fit` reasons auto-raise fitment review tasks (hence the Fitment quick link).

## 2.6 Price Lists

> Context: entered via Sales › Price Lists. [Spec](../../modules/02-sales-pos/2.6-price-lists.md)

```
↰ Sales
▍ PRICE LISTS  (tags)
──────────────────────────────
WORK
  All Price Lists ......... sales.pricelists.index         (list)
  New Price List .......... sales.pricelists.create        (plus-circle)
INSIGHTS
  Price Audit Log ......... sales.pricelists.audit         (history)
QUICK LINKS ⇄
  Counter Sales (POS) ..... sales.pos                      (scan-line)
  Quotations .............. sales.quotes.index             (file-text)
  Promotions .............. sales.promotions.index         (percent)
  Inventory: Categories ... inventory.categories.index     (folder-tree)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- New Price List is Sales/Branch Manager only; Pricing Clerks work inside list detail (items, bulk updates staged as drafts).
- Per-part pricing across lists renders as a tab on part detail (1.1) and is not repeated here.

## 2.7 Promotions & Discounts

> Context: entered via Sales › Promotions & Discounts. [Spec](../../modules/02-sales-pos/2.7-promotions-discounts.md)

```
↰ Sales
▍ PROMOTIONS  (percent)
──────────────────────────────
WORK
  All Promotions .......... sales.promotions.index         (list)
  New Promotion ........... sales.promotions.create        (plus-circle)
QUICK LINKS ⇄
  Counter Sales (POS) ..... sales.pos                      (scan-line)
  Price Lists ............. sales.pricelists.index         (tags)
  Quotations .............. sales.quotes.index             (file-text)
  Inventory: Categories ... inventory.categories.index     (folder-tree)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Approval, pause/resume, and the redemptions/performance/conflicts tabs live on promotion detail (`sales.promotions.show`).
- Creation is Marketing/Pricing Clerk; activation requires Sales Manager approval — status changes are clock-driven, never till-side toggles.

## 2.8 Lay-by Management

> Context: entered via Sales › Lay-by Management. [Spec](../../modules/02-sales-pos/2.8-layby-management.md)

```
↰ Sales
▍ LAY-BYS  (calendar-clock)
──────────────────────────────
WORK
  All Lay-bys ............. sales.laybys.index             (list)          [badge: overdue]
QUICK LINKS ⇄
  Counter Sales (POS) ..... sales.pos                      (scan-line)
  Tax Invoices ............ sales.invoices.index           (receipt)
  Credit Notes & Returns .. sales.credits.index            (rotate-ccw)
  Inventory: Bins ......... inventory.bins.index           (map-pin)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: agreements flagged overdue (no payment for 30+ days past schedule) awaiting follow-up.
- No create item: new lay-bys are born from a POS cart via the deposit tender flow (hence POS first in Quick Links); payments, extensions, swaps, and completion run from lay-by detail.

## 2.9 Delivery Notes

> Context: entered via Sales › Delivery Notes. [Spec](../../modules/02-sales-pos/2.9-delivery-notes.md)

```
↰ Sales
▍ DELIVERY NOTES  (truck)
──────────────────────────────
WORK
  All Delivery Notes ...... sales.deliveries.index         (list)          [badge: open follow-ups]
  Delivery Runs ........... sales.deliveries.runs          (route)
  Driver Run View ......... sales.deliveries.run           (smartphone)
QUICK LINKS ⇄
  Sales Orders ............ sales.orders.index             (clipboard-list)
  Tax Invoices ............ sales.invoices.index           (receipt)
  Credit Notes & Returns .. sales.credits.index            (rotate-ccw)
  Inventory: Bins ......... inventory.bins.index           (map-pin)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: unresolved short/refused/failed follow-ups (each must close as a re-delivery or a credit note — never silently).
- Driver Run View is a mobile, full-screen outcome-capture screen; drivers see only their own run, no sidebar chrome.
- Notes are created from packed orders or posted invoices on the detail/run screens, so there is no standalone create item.
