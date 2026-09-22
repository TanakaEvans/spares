# Workflow: Procure to Pay

> From "we're running low" to "supplier is paid". Covers reorder-triggered and manual purchasing, local and import suppliers.

---

## Full Chain

```
① Trigger
   ├─ Reorder alert: qty_on_hand − qty_reserved ≤ reorder_point
   ├─ Back-order from a sales order (customer special order)
   └─ Manual: buyer decides to stock a new line
      │
      ▼
② Purchase Requisition (PR)  — optional but recommended
   - auto-created from reorder alerts (source='auto')
   [⚠] approval: buyer approves; manager if above threshold
      │
      ▼
③ Purchase Order (PO)
   - buyer selects supplier (system suggests preferred from approved list)
   - price comparison tool: same part across suppliers
   - currency + exchange rate locked at order date (imports)
   [⚠] PO value > threshold → second approval
   - PDF emailed to supplier
   [STOCK] qty_on_order ↑ per line
      │
      ▼
④ Goods arrive → GRN (Goods Received Note)
   - receive against PO (one delivery may cover several POs)
   - count, inspect, reject damaged (qty_rejected + reason)
   [⚠] over-receipt > 10% of PO qty → supervisor approval
   - assign bin locations (putaway), capture batch/serial numbers
   - allocate landed costs (freight/customs/clearing) by value or weight
   - POST:
   [STOCK] PURCHASE_RECEIPT per line, qty_on_hand ↑, qty_on_order ↓
           AVCO recalculated: (old value + receipt value) / (old qty + new qty)
   [GL]    DR Inventory Asset    CR AP Accruals (GRN not yet invoiced)
      │
      ▼
⑤ Supplier invoice arrives → 3-way match
   PO qty/price  ↔  GRN qty  ↔  Invoice amount
   [⚠] tolerance: invoice ≤ GRN value + max(2%, $10) else → disputed
   - matched → approved
   [GL] DR AP Accruals + VAT Input    CR Creditors Control
      │
      ▼
⑥ Payment run (weekly/monthly)
   - accountant selects due invoices (by due_date from payment terms)
   - batch export to bank EFT file
   [GL] DR Creditors Control    CR Bank
   - remittance advice emailed to supplier
      │
      ▼
⑦ PO closed when fully received + invoiced + paid
```

---

## Import Supplier Variant (adds between ③ and ④)

```
PO (foreign currency, e.g. USD/CNY)
      │
      ▼
Proforma invoice → deposit payment (if terms require)
      │
      ▼
Import shipment record created
   status: ordered → shipped → in_transit → customs → cleared → delivered
   - BL/AWB number, ETA, clearing agent tracked
      │
      ▼
Customs clearance
   - duty, VAT on importation, clearing agent fee captured
      │
      ▼
GRN with landed costs
   - freight + insurance + duty + clearing allocated across lines
   - true landed unit cost flows into AVCO
   [⚠] FX variance: rate at payment vs rate at PO
   [GL] variance → FX Gain/Loss account
```

---

## Exception Paths

| Exception | Handling |
|-----------|---------|
| Supplier short-delivers | GRN partial; PO stays `partial`; outstanding qty remains on order or line is closed short |
| Goods damaged on arrival | qty_rejected on GRN line → no stock entry → Return to Supplier flow ([returns-and-credits.md](returns-and-credits.md)) |
| Wrong part delivered | Reject at GRN, request RMA, supplier return |
| Invoice disputes | status `disputed` + written reason; escalated; excluded from payment runs until resolved |
| Supplier price increase on invoice | Fails tolerance → buyer renegotiates or approves variance with reason (audited) |
| PO cancelled after partial receipt | Outstanding lines cancelled; [STOCK] qty_on_order ↓ for cancelled qty |

---

## Who Does What

| Step | Role |
|------|------|
| Reorder review, PR approval | Buyer / Purchasing Officer |
| PO creation + supplier choice | Buyer |
| High-value PO approval | Manager |
| GRN receiving + putaway | Warehouse Staff |
| Rejection sign-off | Warehouse Supervisor |
| Invoice matching | Accounts Clerk |
| Payment run | Accountant (prepare) + Manager (release) |
