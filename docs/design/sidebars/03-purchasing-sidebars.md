# Sub-module Sidebars: Purchasing & Procurement

Per-sub-module sidebar layouts for [Module 3: Purchasing & Procurement](../../modules/03-purchasing-procurement.md), following the universal template and hard rules in [README.md](README.md).

## 3.1 Purchase Requisitions

> Context: entered via Purchasing › Purchase Requisitions. [Spec](../../modules/03-purchasing-procurement/3.1-purchase-requisitions.md)

```
↰ Purchasing
▍ REQUISITIONS  (inbox)
──────────────────────────────
WORK
  All Requisitions ........ purchasing.requisitions.index  (list)          [badge: awaiting approval]
  New Requisition ......... purchasing.requisitions.create (plus-circle)
QUICK LINKS ⇄
  Purchase Orders ......... purchasing.orders.index        (file-text)
  Price Comparison ........ purchasing.price-compare       (git-compare)
  Inventory: Reorder ...... inventory.reorder              (bell)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: submitted PRs in the approval queue (buyer authority or manager threshold), sorted by priority then required date.
- Approve, reject, and Convert-to-PO (supplier-split dialog) run from requisition detail; the price-comparison grid is also embedded there as a side panel.
- Auto-generated PRs (`source = auto`) from the reorder engine land in the same queue — the Inventory: Reorder quick link is the buyer's round trip.

## 3.2 Purchase Orders

> Context: entered via Purchasing › Purchase Orders. [Spec](../../modules/03-purchasing-procurement/3.2-purchase-orders.md)

```
↰ Purchasing
▍ PURCHASE ORDERS  (file-text)
──────────────────────────────
WORK
  All Purchase Orders ..... purchasing.orders.index        (list)          [badge: awaiting approval]
  New Purchase Order ...... purchasing.orders.create       (plus-circle)
QUICK LINKS ⇄
  Goods Received Notes .... purchasing.grns.index          (package-check)
  Supplier Invoices ....... purchasing.invoices.index      (file-check)
  Requisitions ............ purchasing.requisitions.index  (inbox)
  Import Management ....... purchasing.imports.index       (ship)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: submitted POs pending level-1/level-2 value-threshold approval (self-approval is blocked; escalates upward).
- New PO offers "from requisition" and "from blanket order" starters; revisions, send/confirm, and close actions run from PO detail.
- Warehouse and AP roles see this sidebar read-only (delivery planning and match reference).

## 3.3 Goods Received Notes

> Context: entered via Purchasing › Goods Received Notes. [Spec](../../modules/03-purchasing-procurement/3.3-goods-received-notes.md)

```
↰ Purchasing
▍ GOODS RECEIVING  (package-check)
──────────────────────────────
WORK
  Receive Goods ........... purchasing.grns.create         (package-check) [badge: POs awaiting receipt]
  All GRNs ................ purchasing.grns.index          (list)
QUICK LINKS ⇄
  Purchase Orders ......... purchasing.orders.index        (file-text)
  Supplier Invoices ....... purchasing.invoices.index      (file-check)
  Returns to Supplier ..... purchasing.returns.index       (package-x)
  Import Management ....... purchasing.imports.index       (ship)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Receive Goods is an operational screen: the sidebar auto-collapses to icons during counting/putaway (hover/`[` re-expands).
- Badge: open/partial POs with outstanding quantities awaiting receipt at this branch.
- Posting is Warehouse Supervisor (or clerk within tolerance); rejected quantities auto-draft a supplier return — hence the Returns quick link. Import POs must be received via their shipment (3.7).

## 3.4 Supplier Invoices

> Context: entered via Purchasing › Supplier Invoices. [Spec](../../modules/03-purchasing-procurement/3.4-supplier-invoices.md)

```
↰ Purchasing
▍ SUPPLIER INVOICES  (file-check)
──────────────────────────────
WORK
  AP Invoice Queue ........ purchasing.invoices.index      (list)          [badge: disputed]
  Capture Invoice ......... purchasing.invoices.create     (plus-circle)
QUICK LINKS ⇄
  Purchase Orders ......... purchasing.orders.index        (file-text)
  Goods Received Notes .... purchasing.grns.index          (package-check)
  Supplier Credit Notes ... purchasing.credit-notes.index  (file-minus)
  Returns to Supplier ..... purchasing.returns.index       (package-x)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: invoices in `disputed` status on the Finance Manager's queue (unpayable until resolved); the list also carries unmatched and due-soon filters.
- The 3-way match grid (PO ↔ GRN ↔ invoice) runs inside Capture Invoice; out-of-tolerance overrides are Finance Manager only and audit-listed.

## 3.5 Supplier Credit Notes

> Context: entered via Purchasing › Supplier Credit Notes. [Spec](../../modules/03-purchasing-procurement/3.5-supplier-credit-notes.md)

```
↰ Purchasing
▍ SUPPLIER CREDITS  (file-minus)
──────────────────────────────
WORK
  All Credit Notes ........ purchasing.credit-notes.index  (list)
  Capture Credit Note ..... purchasing.credit-notes.create (plus-circle)
QUICK LINKS ⇄
  Supplier Invoices ....... purchasing.invoices.index      (file-check)
  Returns to Supplier ..... purchasing.returns.index       (package-x)
  Workshop: Warranty Claims workshop.claims.index          (shield-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Capture links each credit to its cause (invoice, return, or warranty claim); unlinked rebate/goodwill credits always route to Finance Manager approval.
- Posting a return-linked credit closes the return (`credit_received`) — Quick Links mirror those cause documents.

## 3.6 Returns to Supplier

> Context: entered via Purchasing › Returns to Supplier. [Spec](../../modules/03-purchasing-procurement/3.6-returns-to-supplier.md)

```
↰ Purchasing
▍ SUPPLIER RETURNS  (package-x)
──────────────────────────────
WORK
  All Returns ............. purchasing.returns.index       (list)          [badge: chase list]
  New Return .............. purchasing.returns.create      (plus-circle)
QUICK LINKS ⇄
  Goods Received Notes .... purchasing.grns.index          (package-check)
  Supplier Credit Notes ... purchasing.credit-notes.index  (file-minus)
  Workshop: Warranty Claims workshop.claims.index          (shield-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: returns shipped more than 30 days ago with no credit received (the chase list, with one-click chase email).
- RMA capture, shipment posting (`RETURN_OUT`), and credit linking run from return detail; drafts are commonly auto-created from GRN rejections.
- Excess-stock returns need Procurement Manager approval (restocking fees disclosed at approval).

## 3.7 Import Management

> Context: entered via Purchasing › Import Management. [Spec](../../modules/03-purchasing-procurement/3.7-import-management.md)

```
↰ Purchasing
▍ IMPORTS  (ship)
──────────────────────────────
WORK
  Shipments Board ......... purchasing.imports.index       (ship)          [badge: customs holds]
  New Shipment ............ purchasing.imports.create      (plus-circle)
QUICK LINKS ⇄
  Purchase Orders ......... purchasing.orders.index        (file-text)
  Goods Received Notes .... purchasing.grns.index          (package-check)
  Supplier Invoices ....... purchasing.invoices.index      (file-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge: shipments held at customs (flagged red on the board while demurrage accrues).
- Milestones, documents, landed costs, and the Receive (open GRN) hand-off all run from shipment detail; sales roles get read-only ETA visibility for customer promises.

## 3.8 Price Comparison

> Context: entered via Purchasing › Price Comparison. [Spec](../../modules/03-purchasing-procurement/3.8-price-comparison.md)

```
↰ Purchasing
▍ PRICE COMPARISON  (git-compare)
──────────────────────────────
WORK
  Compare Prices .......... purchasing.price-compare       (git-compare)
QUICK LINKS ⇄
  Requisitions ............ purchasing.requisitions.index  (inbox)
  Purchase Orders ......... purchasing.orders.index        (file-text)
  Import Management ....... purchasing.imports.index       (ship)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Decision-support only: the screen creates no documents — "Create PO" hands off to 3.2 pre-filled, and the same grid embeds as a side panel in requisition conversion (3.1).
- Buyer and Procurement Manager roles only; counter sales never see cost data. Preferred-supplier flags are Procurement Manager.
