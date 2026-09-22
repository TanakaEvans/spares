# Sub-module Sidebars: Finance & Accounts

Sub-module sidebars for [Module 7: Finance & Accounts](../../modules/07-finance-accounts.md), built to the [universal template](README.md).

## 7.1 Chart of Accounts

> Context: entered via Finance › Chart of Accounts. [Spec](../../modules/07-finance-accounts/7.1-chart-of-accounts.md)

```
↰ Finance
▍ CHART OF ACCOUNTS  (list-tree)
──────────────────────────────
WORK
  COA Browser ............. finance.coa.index               (list-tree)
SETUP
  Posting Mappings ........ finance.coa.index (mappings)    (route)
QUICK LINKS ⇄
  GL Enquiry .............. finance.gl.index                (search)
  Trial Balance ........... finance.reports.trial-balance   (scale)
  VAT Returns ............. finance.vat.index               (percent)
  AP Payments ............. finance.payments.index          (wallet)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The browser is the hierarchical COA with balances; account detail (settings, posting history, mapping usage) opens within it.
- Posting Mappings (transaction-type → account configuration) is financial-administrator only; SETUP hides for other roles.
- AR/AP sub-ledgers sit behind the debtors/creditors control accounts — control accounts take no manual postings.

## 7.2 General Ledger

> Context: entered via Finance › General Ledger. [Spec](../../modules/07-finance-accounts/7.2-general-ledger.md)

```
↰ Finance
▍ GENERAL LEDGER  (book-open)
──────────────────────────────
WORK
  GL Enquiry .............. finance.gl.index                (search)
  Journals ................ finance.journals.index          (book-open)     [badge: draft journals]
  New Journal ............. finance.journals.create         (plus-circle)
QUICK LINKS ⇄
  Chart of Accounts ....... finance.coa.index               (list-tree)
  Trial Balance ........... finance.reports.trial-balance   (scale)
  Period Management ....... finance.periods.index           (calendar-check)
  AR Receipts ............. finance.receipts.index          (banknote)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Journal entry is a keyboard-first operational screen: sidebar auto-collapses to icons.
- `draft journals` counts unposted drafts; posted journals are immutable (view-only detail) and every posting is gated by period status (7.8).
- Enquiry filters journal lines by account, period and source; sub-ledger postings (7.3/7.4) arrive here read-only.

## 7.3 Accounts Receivable

> Context: entered via Finance › Accounts Receivable. [Spec](../../modules/07-finance-accounts/7.3-accounts-receivable.md)

```
↰ Finance
▍ ACCOUNTS RECEIVABLE  (banknote)
──────────────────────────────
WORK
  AR Receipts ............. finance.receipts.index          (banknote)      [badge: unapplied credits]
  New Receipt ............. finance.receipts.create         (plus-circle)
INSIGHTS
  AR Ageing ............... finance.reports.ar-ageing       (file-bar-chart)
QUICK LINKS ⇄
  Bank Reconciliation ..... finance.bank.reconcile          (git-compare)
  Customers: Credit Mgmt .. customers.credit                (gauge)
  Customers: Statements ... customers.statement             (file-text)
  General Ledger .......... finance.gl.index                (book-open)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Receipt capture/allocation is a keyboard-first operational screen: sidebar auto-collapses to icons.
- `unapplied credits` counts posted receipts with unallocated amounts; the register also splits drafts vs posted.
- The per-customer ledger renders via the customer profile / GL enquiry; ageing (served by 7.7) drills to invoices and feeds credit management and statements (5.3/5.5).

## 7.4 Accounts Payable

> Context: entered via Finance › Accounts Payable. [Spec](../../modules/07-finance-accounts/7.4-accounts-payable.md)

```
↰ Finance
▍ ACCOUNTS PAYABLE  (wallet)
──────────────────────────────
WORK
  AP Payments ............. finance.payments.index          (wallet)
  Payment Run ............. finance.payment-run             (list-checks)
QUICK LINKS ⇄
  Bank & Cash ............. finance.bank.index              (landmark)
  Suppliers: Profiles ..... suppliers.index                 (factory)
  VAT Returns ............. finance.vat.index               (percent)
  General Ledger .......... finance.gl.index                (book-open)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Payment Run (build → review → approve → export) is a keyboard-first operational screen: sidebar auto-collapses to icons; approval is segregated from capture.
- Supplier invoice capture and three-way match are reached from the Purchasing/AP capture screens, and the per-supplier ledger renders on the supplier profile — neither is a sidebar destination here.
- Payments go only to verified bank details (6.1); remittance advices route to the supplier's Accounts contact (6.5); input VAT flows to 7.6.

## 7.5 Cash & Bank Management

> Context: entered via Finance › Cash & Bank. [Spec](../../modules/07-finance-accounts/7.5-cash-bank-management.md)

```
↰ Finance
▍ CASH & BANK  (landmark)
──────────────────────────────
WORK
  Bank Accounts ........... finance.bank.index              (landmark)
  Bank Reconciliation ..... finance.bank.reconcile          (git-compare)
INSIGHTS
  Reconciliation History .. finance.bank.index (history)    (history)
QUICK LINKS ⇄
  AR Receipts ............. finance.receipts.index          (banknote)
  AP Payments ............. finance.payments.index          (wallet)
  General Ledger .......... finance.gl.index                (book-open)
  Period Management ....... finance.periods.index           (calendar-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Bank reconciliation (import → match → adjust → complete) is a keyboard-first operational screen: sidebar auto-collapses to icons; statement import and parser-profile selection open within it.
- The accounts register shows GL-linked balances and reconciliation status; a completed reconciliation is a period-close gate (7.8).
- Deposits match AR receipts (7.3), EFT batches match AP payment runs (7.4), and adjustments post journals to the GL (7.2).

## 7.6 VAT Management

> Context: entered via Finance › VAT Management. [Spec](../../modules/07-finance-accounts/7.6-vat-management.md)

```
↰ Finance
▍ VAT MANAGEMENT  (percent)
──────────────────────────────
WORK
  VAT Returns ............. finance.vat.index               (percent)
INSIGHTS
  VAT Summary Report ...... finance.reports.vat-summary     (file-search)
QUICK LINKS ⇄
  General Ledger .......... finance.gl.index                (book-open)
  AP Payments ............. finance.payments.index          (wallet)
  Period Management ....... finance.periods.index           (calendar-check)
  Chart of Accounts ....... finance.coa.index               (list-tree)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The register carries drafts, submitted and paid returns plus the new-return action; return detail (boxes, workings, drill-downs, exception checks) opens within it.
- The summary report (served by 7.7) covers input/output VAT for any period outside the formal return.
- Periods must be closed before submission (7.8); corrections post journals via 7.2 against the reserved VAT accounts (7.1).

## 7.7 Financial Reporting

> Context: entered via Finance › Financial Reporting. [Spec](../../modules/07-finance-accounts/7.7-financial-reporting.md)

```
↰ Finance
▍ FINANCIAL REPORTING  (bar-chart-3)
──────────────────────────────
INSIGHTS
  Trial Balance ........... finance.reports.trial-balance   (scale)
  Income Statement ........ finance.reports.pl              (trending-up)
  Balance Sheet ........... finance.reports.balance-sheet   (layout-list)
  All Reports ............. finance.reports (menu)          (bar-chart-3)
QUICK LINKS ⇄
  General Ledger .......... finance.gl.index                (book-open)
  Chart of Accounts ....... finance.coa.index               (list-tree)
  Period Management ....... finance.periods.index           (calendar-check)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- This sub-module is pure reporting, so WORK is omitted; the report menu carries the remainder (cash flow, AR/AP ageing, VAT summary, margin by category, sales vs budget, bank rec).
- Report structure follows COA types and categories (7.1); every figure decomposes into posted journal lines (7.2), and closed periods (7.8) make figures final and reproducible.
- Statement-level reports are permission-gated to finance roles.

## 7.8 Period Management

> Context: entered via Finance › Period Management. [Spec](../../modules/07-finance-accounts/7.8-period-management.md)

```
↰ Finance
▍ PERIOD MANAGEMENT  (calendar-check)
──────────────────────────────
WORK
  Period Grid ............. finance.periods.index           (calendar-check)
  Close Checklist ......... finance.periods.index (close)   (list-checks)
INSIGHTS
  Close History ........... finance.periods.index (history) (history)
QUICK LINKS ⇄
  General Ledger .......... finance.gl.index                (book-open)
  Bank Reconciliation ..... finance.bank.reconcile          (git-compare)
  VAT Returns ............. finance.vat.index               (percent)
  Trial Balance ........... finance.reports.trial-balance   (scale)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The grid shows years/periods with statuses and close actions; the per-period checklist tracks gate status (AR/AP control recs, bank rec), drill-throughs and sign-offs; history records closes, reopens, snapshots and the audit trail.
- Close and reopen are financial-controller only; the whole sidebar is permission-gated to finance administration roles.
- Period status enforced here gates every GL posting (7.2) and VAT submission (7.6).
