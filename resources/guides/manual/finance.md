# Money & Accounts

The system keeps a full double-entry ledger for you automatically — every sale, purchase and payment posts itself. This section is how you work with the money side day to day.

## Daily cash-up (end of day)

**Reports → Daily Cash-up** (route:reports.cashup).

1. Pick the **date** and **branch**.
2. See the day's takings split by **method** (cash, card, EFT, account) and by **cashier**. "Banked" is real money; "On account" is credit given.
3. Count your drawer against **Cash in drawer**.
4. Click **Print cash-up** for a signed slip.

## Record a customer payment (receipt)

When an account customer pays what they owe:

1. **Finance → Receipts → New** (route:finance.receipts.index).
2. Pick the customer and the amount and method.
3. **Allocate** it against their open invoices (oldest first, or pick them).
4. Post. Their balance comes down; the bank goes up.

## Pay suppliers

See (guide:purchasing) → "Pay the supplier", or run a **payment run** (route:finance.payment-run.index) to settle many at once.

## Read the books

- **Trial Balance** (route:finance.reports.trial-balance) — every account's balance; debits must equal credits.
- **Income Statement** (route:finance.reports.income-statement) — profit for a period.
- **Balance Sheet** (route:finance.reports.balance-sheet) — what you own and owe.
- **General Ledger** (route:finance.gl.index) — drill into any account's transactions; every line links to its document.

## VAT return

**Finance → VAT** (route:finance.vat.index): the return is worked out for you (output VAT less input VAT) for the period you choose.

## Manual journals

Only for corrections the normal screens can't make. **Finance → Journals** (route:finance.journals.index). Control accounts are protected and posted documents can only be corrected with a reversing entry — the system enforces this so the books can't be fiddled.

## Is everything balanced?

**System Administration → System Health** (route:admin.health) runs the integrity checks live — books balanced, stock matches the ledger, control accounts reconcile. Green means trustworthy.
