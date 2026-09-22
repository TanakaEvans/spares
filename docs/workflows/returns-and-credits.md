# Workflow: Returns & Credits (Both Directions)

> Customer returns to us (credit notes) and our returns to suppliers (RMA). These are the two flows most often done wrong in spares businesses — wrong stock, wrong VAT, unrecovered money.

---

## Direction 1: Customer Returns a Part to Us

```
① Customer at counter with part + proof of purchase
      │
      ▼
② Staff locates ORIGINAL INVOICE (scan receipt barcode / search)
   [⚠] No standalone credits — every credit note references an invoice
      │
      ▼
③ Inspect part & classify
   ├─ resaleable (unopened, undamaged) ──────► stock returns
   ├─ defective under warranty ─────────────► warranty path (below)
   ├─ wrong part supplied (our error) ──────► stock returns, no restock fee
   └─ electrical/special order ─────────────► [⚠] no-return policy → manager only
      │
      ▼
④ Create Credit Note (linked to invoice)
   [⚠] credit qty ≤ originally invoiced qty (minus prior credits)
   [⚠] price = price actually paid (from invoice line, incl. discounts)
      │
      ▼
⑤ Post
   [STOCK] RETURN_IN per resaleable line (qty_on_hand ↑)
           defective items → quarantine bin, NOT sellable stock
   [GL]    DR Sales Returns          CR Debtors Control (or Cash)
           DR VAT Output (reversal)
           DR Inventory Asset        CR COGS   (resaleable only)
      │
      ▼
⑥ Settle
   ├─ refund cash [⚠] only if original was cash + same day, else manager PIN
   ├─ credit customer's account (trade customers — default)
   └─ exchange: credit note + new invoice in one visit
```

### Defective-part continuation (recover from supplier)
```
Defective part in quarantine bin
      │
      ▼
Was it sold from a supplier batch under warranty?
      │ yes
      ▼
Supplier Return raised (reason: warranty) → RMA → ship → supplier credit
   [STOCK] RETURN_OUT from quarantine
   [GL] DR Creditors Control  CR Inventory (quarantine value)
```

---

## Direction 2: We Return to a Supplier

```
① Trigger
   ├─ GRN rejections (damaged / wrong part on arrival)
   ├─ warranty claim-backs (defective customer returns)
   └─ excess stock / dead stock (negotiated buy-back)
      │
      ▼
② Supplier Return created — must reference the original GRN
   [⚠] can only return parts actually received from that supplier
      │
      ▼
③ Request RMA from supplier
   [⚠] cannot mark 'shipped' without supplier RMA number
      │
      ▼
④ Pack & ship (return document printed, RMA on label)
   [STOCK] RETURN_OUT (qty_on_hand ↓, at AVCO cost)
      │
      ▼
⑤ Supplier credit note received & captured
   [⚠] chase list: returns shipped > 30 days without credit note
   [GL] DR Creditors Control   CR Inventory Asset (+ VAT input reversal)
      │
      ▼
⑥ Credit applied in next payment run (reduces amount paid)
```

---

## VAT Rules (both directions)

| Event | VAT effect |
|-------|-----------|
| Customer credit note | VAT Output reversed at the ORIGINAL invoice rate (not today's rate) |
| Supplier credit note | VAT Input reversed at the original supplier invoice rate |
| Credit note in a later VAT period | Legal and normal — it reduces that period's return, never re-opens the old one |

---

## Abuse Controls

- Return rate per customer reported monthly; > 10% flags review.
- Serial-tracked parts: serial on the returned unit must match a serial sold on that invoice.
- Same-part-returned-twice detection (part + invoice + qty already credited).
- All refunds above a threshold require manager PIN; all overrides audited with user + reason.
