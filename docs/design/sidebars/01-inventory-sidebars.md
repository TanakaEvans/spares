# Sub-module Sidebars: Inventory Management

Per-sub-module sidebar layouts for [Module 1: Inventory Management](../../modules/01-inventory-management.md), following the universal template and hard rules in [README.md](README.md).

## 1.1 Parts Catalogue

> Context: entered via Inventory › Parts Catalogue. [Spec](../../modules/01-inventory-management/1.1-parts-catalogue.md)

```
↰ Inventory
▍ PARTS CATALOGUE  (package)
──────────────────────────────
WORK
  All Parts ............... inventory.parts.index          (list)
  New Part ................ inventory.parts.create         (plus-circle)
SETUP
  Brands .................. inventory.brands.index         (tag)
QUICK LINKS ⇄
  Stock Control ........... inventory.stock.index          (layers)
  Categories .............. inventory.categories.index     (folder-tree)
  Fitment Guide ........... inventory.fitments.vehicle     (car)
  Reorder Report .......... inventory.reorder              (bell)
  Serial & Batch .......... inventory.serials.index        (scan-barcode)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Part detail (`inventory.parts.show`) is reached from the list, not the sidebar; its tabs (stock, fitments, cross-refs, history) are the hub other sub-modules deep-link into.
- SETUP is permission-gated: Brands is Inventory Clerk and above; cashiers see WORK as read-only search.

## 1.2 Stock Control

> Context: entered via Inventory › Stock Control. [Spec](../../modules/01-inventory-management/1.2-stock-control.md)

```
↰ Inventory
▍ STOCK CONTROL  (layers)
──────────────────────────────
WORK
  Stock Levels ............ inventory.stock.index          (layers)
  Adjustments ............. inventory.adjustments.index    (sliders-horizontal) [badge: awaiting approval]
  Transfers ............... inventory.transfers.index      (arrow-left-right)   [badge: in transit]
QUICK LINKS ⇄
  Bin Locations ........... inventory.bins.index           (map-pin)
  Stock Takes ............. inventory.stocktakes.index     (clipboard-check)
  Reorder Report .......... inventory.reorder              (bell)
  Parts Catalogue ......... inventory.parts.index          (package)
  Serial & Batch .......... inventory.serials.index        (scan-barcode)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badges: adjustments in `awaiting_approval` status (over the per-branch value threshold); transfers dispatched but not yet received (`qty_in_transit` > 0).
- The per-part movement ledger renders as a tab on part detail (`inventory.parts.show`, owned by 1.1), so it is deliberately absent here.
- Adjustment posting and approval rows are permission-filtered (Storeman drafts; Inventory Manager approves/posts).

## 1.3 Bin Locations

> Context: entered via Inventory › Bin Locations. [Spec](../../modules/01-inventory-management/1.3-bin-locations.md)

```
↰ Inventory
▍ BIN LOCATIONS  (map-pin)
──────────────────────────────
WORK
  Bin Map ................. inventory.bins.index           (map-pin)
  New Bin ................. inventory.bins.create          (plus-circle)
  Bulk Generate ........... inventory.bins.generate        (grid-3x3)
QUICK LINKS ⇄
  Stock Control ........... inventory.stock.index          (layers)
  Stock Takes ............. inventory.stocktakes.index     (clipboard-check)
  Parts Catalogue ......... inventory.parts.index          (package)
  Sales: Delivery Notes ... sales.deliveries.index         (truck)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Bin detail (`inventory.bins.show`), label printing, and bin-to-bin moves open from the map's side panel, not the sidebar.
- New Bin / Bulk Generate are Inventory Clerk and above; storemen see the map and move actions only.

## 1.4 Stock Takes

> Context: entered via Inventory › Stock Takes. [Spec](../../modules/01-inventory-management/1.4-stock-takes.md)

```
↰ Inventory
▍ STOCK TAKES  (clipboard-check)
──────────────────────────────
WORK
  All Stock Takes ......... inventory.stocktakes.index     (list)
  New Stock Take .......... inventory.stocktakes.create    (plus-circle)
  Count Entry ............. inventory.stocktakes.count     (hash)          [badge: open counts]
  Variance Review ......... inventory.stocktakes.review    (scale)         [badge: pending]
INSIGHTS
  Variance History ........ inventory.stocktakes.history   (history)
QUICK LINKS ⇄
  Stock Control ........... inventory.stock.index          (layers)
  Bin Locations ........... inventory.bins.index           (map-pin)
  Adjustments ............. inventory.adjustments.index    (sliders-horizontal)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Count Entry is an operational screen: the sidebar auto-collapses to icons while counting (hover/`[` re-expands).
- Badges: sheets assigned to the current user still uncounted (open counts); lines awaiting variance review/recount (pending).
- Counters see Count Entry only; Variance Review and posting are Inventory Manager and above (blind-count rule keeps system quantities hidden pre-review).

## 1.5 Reorder Management

> Context: entered via Inventory › Reorder Management. [Spec](../../modules/01-inventory-management/1.5-reorder-management.md)

```
↰ Inventory
▍ REORDER MANAGEMENT  (bell)
──────────────────────────────
WORK
  Reorder Report .......... inventory.reorder              (bell)          [badge: below reorder]
INSIGHTS
  ABC Analysis ............ inventory.reorder.abc          (bar-chart-3)
QUICK LINKS ⇄
  Stock Control ........... inventory.stock.index          (layers)
  Sales: Sales Orders ..... sales.orders.index             (clipboard-list)
  Parts Catalogue ......... inventory.parts.index          (package)
  Stock Takes ............. inventory.stocktakes.index     (clipboard-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: count of parts where `available ≤ reorder_point` (excluding covered/snoozed rows) for the selected branch.
- Per-part reorder settings are edited on the part detail stock tab (1.1); requisition conversion hands off to Purchasing — no purchasing routes appear here.
- ABC threshold configuration is Inventory Manager only.

## 1.6 Parts Fitment Guide

> Context: entered via Inventory › Parts Fitment Guide. [Spec](../../modules/01-inventory-management/1.6-parts-fitment-guide.md)

```
↰ Inventory
▍ FITMENT GUIDE  (car)
──────────────────────────────
WORK
  Vehicle Parts Lookup .... inventory.fitments.vehicle     (car)
  Review Queue ............ inventory.fitments.review      (flag)          [badge: pending reviews]
QUICK LINKS ⇄
  Parts Catalogue ......... inventory.parts.index          (package)
  Sales: Counter Sales .... sales.pos                      (scan-line)
  Sales: Credit Notes ..... sales.credits.index            (rotate-ccw)
  Categories .............. inventory.categories.index     (folder-tree)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: reported wrong fitments plus unconfirmed `manual` records awaiting manager confirmation.
- Per-part fitment editing lives on the part detail fitments tab (1.1); this sidebar carries the vehicle-first lookup and the review workflow.
- Review Queue actions (confirm/correct/reject) are Inventory Manager only.

## 1.7 Serial & Batch Tracking

> Context: entered via Inventory › Serial & Batch Tracking. [Spec](../../modules/01-inventory-management/1.7-serial-batch-tracking.md)

```
↰ Inventory
▍ SERIAL & BATCH  (scan-barcode)
──────────────────────────────
WORK
  Serial Register ......... inventory.serials.index        (scan-barcode)
  Batch Register .......... inventory.batches.index        (boxes)         [badge: expiring soon]
QUICK LINKS ⇄
  Stock Control ........... inventory.stock.index          (layers)
  Parts Catalogue ......... inventory.parts.index          (package)
  Sales: Credit Notes ..... sales.credits.index            (rotate-ccw)
  Stock Takes ............. inventory.stocktakes.index     (clipboard-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: batches inside the 90/30/7-day expiry warning windows (expired batches are already sale-blocked).
- Serial detail (`inventory.serials.show`) opens from the register; capture happens inside GRN receiving and POS flows, not from this sidebar.
- Scrapping serials, expiry write-offs, and warranty-date edits are Inventory Manager only.

## 1.8 Category Management

> Context: entered via Inventory › Category Management. [Spec](../../modules/01-inventory-management/1.8-category-management.md)

```
↰ Inventory
▍ CATEGORIES  (folder-tree)
──────────────────────────────
WORK
  Category Tree ........... inventory.categories.index     (folder-tree)
  Bulk Re-assign .......... inventory.categories.reassign  (shuffle)
QUICK LINKS ⇄
  Parts Catalogue ......... inventory.parts.index          (package)
  Fitment Guide ........... inventory.fitments.vehicle     (car)
  Sales: Promotions ....... sales.promotions.index         (percent)
  Sales: Counter Sales .... sales.pos                      (scan-line)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Category detail (`inventory.categories.show`) opens from the tree's right-hand panel; merges and moves are Inventory Manager actions.
- GL/VAT default columns in the detail panel are visible to all but editable by Finance Manager only.
