# About this page

The one screen that proves the whole ERP is sound. Every row is an **invariant** the system relies on, recomputed live from the ledger — so this page can never show a stale "all clear".

## What it checks
- **Financial:** every posted journal balances, the trial balance balances, the AR and AP sub-ledgers equal their control accounts, stock value equals the Inventory control (1310), and the current period is open.
- **Stock:** cached stock levels equal the ledger sums; no negative stock.
- **Hygiene:** no documents stuck in draft.

## Fixing
A red or amber row links to the offending records. Some carry a one-click **Fix** — e.g. "Stock value = Inventory control" offers to post the **opening inventory journal** (DR 1310 / CR Opening Balance Equity), which reconciles stock loaded during migration.

## Related
- [Trial Balance](route:finance.reports.trial-balance) · [Stock Levels](route:inventory.stock.index) · [Periods](route:finance.periods.index)
