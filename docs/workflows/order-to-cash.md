# Workflow: Order to Cash

> From a customer wanting a part to money in the bank. Covers the three entry paths: walk-in counter sale, quotation-led sale, and back-order.

---

## Path A: Walk-in Counter Sale (the 90% case)

```
Customer at counter
      │
      ▼
① Identify part
   ├─ scan barcode ──────────────► parts.barcode_ean lookup
   ├─ type part / OEM number ────► cross-reference search (incl. supersessions)
   └─ "for my Hilux 2016" ───────► vehicle → fitment guide filter
      │
      ▼
② Add to cart (POS screen)
   [⚠] availability check: qty_on_hand − qty_reserved ≥ qty
   [⚠] price resolution: customer price list → promotion → retail
      │
      ▼
③ Select customer
   ├─ Cash Customer (default, anonymous)
   └─ Named customer ── trade account?
          [⚠] credit check: balance + sale ≤ credit_limit
          [⚠] ageing check: no invoices > 60 days overdue
          [⚠] hold check: not on_hold
          └─ fail → supervisor PIN override or cash payment
      │
      ▼
④ Payment
   ├─ Cash → change calculated, drawer opens
   ├─ Card → reference captured
   ├─ Split tender (part cash, part card)
   └─ On account → no money taken, invoice added to AR
      │
      ▼
⑤ Post invoice (single atomic transaction)
   [STOCK] SALE ledger entry per line, qty_on_hand ↓
   [GL]    DR Cash/Bank (or Debtors Control if on account)
           CR Sales Revenue (per category account)
           CR VAT Output
   [GL]    DR Cost of Goods Sold (AVCO unit cost × qty)
           CR Inventory Asset
   Loyalty points earned (retail customers)
      │
      ▼
⑥ Receipt prints (80mm thermal) / A4 tax invoice on request
```

**Timing target:** under 90 seconds scan-to-receipt.

### Failure handling at step ⑤
The posting is wrapped in `DB::transaction()` with `lockForUpdate()` on stock rows. If any part fails (e.g. concurrent sale took the last unit), the whole invoice rolls back and the cashier sees which line failed.

---

## Path B: Quotation → Order → Invoice

```
① Quote created (Sales module)
   - no stock reserved, no GL impact
   - PDF emailed to customer, expiry default 30 days
      │  customer accepts
      ▼
② Convert to Sales Order
   [⚠] re-price check: alert if prices changed since quote
   [⚠] expiry check: expired quotes cannot convert
   [STOCK] qty_reserved ↑ per line (no on-hand change)
   - lines without stock → back-order (Path C)
      │
      ▼
③ Pick & pack
   - picking slip prints with bin locations
   - status: confirmed → picking → packed
      │
      ▼
④ Dispatch / collection
   - delivery note prints (if delivered)
      │
      ▼
⑤ Invoice posted  → identical postings to Path A step ⑤
   [STOCK] reservation released + SALE posted
      │
      ▼
⑥ Payment (immediately, or later via AR receipt — see below)
```

---

## Path C: Back-order (part not in stock)

```
Sales order line: qty > available
      │
      ▼
Line flagged is_back_order
      │
      ▼
Auto purchase requisition raised (if setting enabled)
   - linked to customer + sales order
   - priority: urgent
      │
      ▼
Buyer converts to PO → supplier delivers → GRN posted
      │
      ▼
System alert: "Back-ordered part received for SO-xxxx / customer X"
      │
      ▼
Customer notified (SMS/email) → collection → invoice (Path A ⑤)
```

---

## Getting Paid on Account (AR side)

```
Invoice on account (unpaid, in AR ageing)
      │
      ▼
Month-end: statement emailed to customer (1st of month)
      │
      ▼
Customer pays (EFT / cash at counter)
      │
      ▼
Receipt captured (Finance → AR Receipts)
   - one receipt can cover many invoices (allocations)
   [GL] DR Bank   CR Debtors Control
      │
      ▼
Ageing updated; customer released from hold if applicable
      │
      ▼
Overdue path: 30d reminder email → 60d auto-hold → 90d handed to collections list
```

---

## Documents produced in this workflow

| Document | Number format | When |
|----------|--------------|------|
| Quotation | QT-YYYYMMDD-XXXX | Path B ① |
| Sales Order | SO-YYYYMMDD-XXXX | Path B ② |
| Picking Slip | (from SO) | Path B ③ |
| Delivery Note | DN-YYYYMMDD-XXXX | Path B ④ |
| Tax Invoice | INV-YYYYMMDD-XXXX (gapless) | ⑤ |
| Receipt (payment) | RCP-YYYYMMDD-XXXX | Payment |
| Statement | (monthly) | 1st of month |
