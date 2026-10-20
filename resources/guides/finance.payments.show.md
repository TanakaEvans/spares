# About this page

One posted supplier payment. Payments are **immutable** — the figures here are exactly what was posted (DR Creditors control / CR Bank) and the journal link opens the entry in the GL.

## Reading the page
- **Amount paid** is what left the bank; **Allocated** is how much was matched to supplier invoices; **Unallocated** is a prepayment on the supplier's account.
- **Allocations** list every supplier invoice the payment settled, fully or partly, with the supplier's own reference. Click an invoice number to open the 3-way-matched invoice.
- **Batch** shows the payment-run reference when this payment was created by a Payment Run; "single payment" means it was captured on its own.
- **Connected to** shows the chain Supplier invoice(s) → Payment and links the supplier, journal and the user who captured it.

## Corrections
A payment cannot be edited. If it was captured against the wrong supplier or amount, post a reversing journal through Finance → Journals, then capture the correct payment.

## Related
- [Supplier Payments](route:finance.payments.index) · [Capture a payment](route:finance.payments.create) · [Payment Run](route:finance.payment-run.index) · [Journals](route:finance.journals.index)
