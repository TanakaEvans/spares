# Workflow: New Part Onboarding

> How a part number enters the system — the front door for the ever-growing catalogue. Four entry routes, one quality gate.

```
Entry routes
────────────
A) Supplier price list import      (bulk — the main route)
B) TECDOC lookup miss at POS       (on-demand — customer asked for it)
C) GRN surprise                    (supplier delivered an unlisted item)
D) Manual creation                 (buyer decides to stock a new line)
      │
      ▼
① Draft part created
   - part_number assigned (internal scheme or supplier number adopted)
   - description, category, brand, unit of measure
   - source route recorded
      │
      ▼
② Enrichment
   □ OEM number + cross-references (TECDOC if subscribed, else manual/supplier data)
   □ Fitment records: which makes/models/years (min: the vehicle it was
     requested for)
   □ Barcode: scan EAN from packaging → barcode_ean;
     system generates internal Code 128 label
   □ Image (photo at the counter is fine)
   □ Supersession check: does this replace an existing number?
      │
      ▼
③ Commercial setup [⚠ quality gate — buyer approves]
   □ Cost price (from supplier price list / PO)
   □ Selling prices per price list (retail/trade/wholesale) — margin
     % rules can auto-suggest
   □ VAT code
   □ Approved supplier(s) with preferred flag
   □ Reorder point + reorder qty + max level (can start at 0/0 for
     special-order-only lines)
   □ Default bin location per branch
      │
      ▼
④ Activate (is_active = true)
   - now searchable at POS, orderable on POs
   - route B/C parts often activate same-day; bulk imports activate
     with the price list
```

## Route details

**A — Price list import:** unmatched rows in the import wizard land in a "New parts review" queue. Buyer bulk-edits category/margins and activates in batches. Never auto-activate without review — supplier files contain typos and duplicates.

**B — POS lookup miss:** counter staff hit "Request new part"; a draft is created with the customer's vehicle attached as the first fitment record and the sale is captured as a quote. Buyer completes ②–④, sources it, and the customer is called back.

**C — GRN surprise:** warehouse cannot receive an unknown item into stock. A draft part is created at the GRN screen (supervisor permission), received into quarantine bin, and routed to the buyer for step ③ before it becomes sellable.

**D — Manual:** full create form, same gate.

## Rules

1. A part cannot be sold until it has: category, VAT code, at least one selling price, and `is_active = true`.
2. A part cannot go on a PO until it has at least one approved supplier.
3. Duplicate detection at ①: exact part_number, exact EAN, and fuzzy OEM-number match against cross-references — warns before creating.
4. Every field change in ②–③ is audited (who, when, before/after).
