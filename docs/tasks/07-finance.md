# Tasks: Finance & Accounts

> Phase 4 of the [implementation plan](../implementation-plan.md). Spec: [module doc](../modules/07-finance-accounts.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

The posting engine (`GlPostingService`) exists from Phase 0 and has journalised every invoice/GRN since; this phase builds the accountant's surface on the already-correct ledger.

## 7.1 Chart of Accounts + 7.8 Period Management  ·  [spec 7.1](../modules/07-finance-accounts/7.1-chart-of-accounts.md) · [spec 7.8](../modules/07-finance-accounts/7.8-period-management.md)

### Backend
- [ ] Migration(s): `gl_accounts`, `gl_years`, `gl_periods` (confirm/extend Phase 0 skeletons)
- [ ] Models + relationships + factories; COA seeder from spec (1110 Till → 7000 Other Operating Expenses, control accounts flagged)
- [ ] Period generation: create FY with 12 periods; open/close/lock transitions
- [ ] Form Requests + Policies (only accountant role closes periods)
- [ ] Controllers + routes (`finance.coa.*`, periods) + SystemRoute permission seeds
### Frontend
- [ ] COA page (`finance.coa.index` → `Finance/COA/Index.jsx`) — tree view by type, control-account badges
- [ ] Periods management screen (open/close per period, year status)
- [ ] Sidebar nav entries in finance module nav config
### Tests
- [ ] Feature tests: control account blocks `allow_direct_posting`; closed period rejects posting with re-date suggestion
- [ ] Functional pass: seed COA, open FY2026, close January, attempt a January-dated journal — rejected

## 7.2 General Ledger  ·  [spec](../modules/07-finance-accounts/7.2-general-ledger.md)

### Backend
- [ ] Migration(s): `gl_journals`, `gl_journal_lines` (confirm/extend Phase 0 skeletons)
- [ ] Manual journal entry via GlPostingService; journal_number sequence (JNL-YYYYMMDD-XXXX)
- [ ] Balance constraint: SUM(debit) = SUM(credit) enforced at service layer
- [ ] Reversal creates a new opposite journal (never edits), links `reversed_journal_id`
- [ ] Form Requests + Policies
- [ ] Controllers + routes (`finance.gl.*`, `finance.journals.*`) + SystemRoute permission seeds
### Frontend
- [ ] GL Enquiry (`finance.gl.index` → `Finance/GL/Index.jsx`) — account drill-down, date range, source doc links
- [ ] Journals page (`finance.journals.index` → `Finance/Journals/Index.jsx`) — list + manual journal capture
### Tests
- [ ] Feature tests: unbalanced journal rejected; manual posting to control account rejected; reversal produces mirror lines
- [ ] Unit tests: GlPostingService balance + period validation
- [ ] Functional pass: post journal with unbalanced lines — rejected; post balanced accrual then reverse it — GL nets to zero

## 7.3 AR Receipts & Allocations + 5.5 Statements + Ageing  ·  [spec 7.3](../modules/07-finance-accounts/7.3-accounts-receivable.md) · [spec 5.5](../modules/05-customer-management/5.5-statements.md)

### Backend
- [ ] Migration(s): `customer_receipts`, `customer_receipt_payment_lines`, `receipt_allocations`
- [ ] Models + factories; receipt_number sequence (RCP-YYYYMMDD-XXXX)
- [ ] Posting: DR Bank, CR AR Control via GlPostingService; split allocation across multiple invoices
- [ ] Ageing calc (current / 30 / 60 / 90+); unallocated receipts surface as "Unapplied Credits"
- [ ] Customer statement generation (PDF via print pipeline) + email delivery
- [ ] Form Requests + Policies; controllers + routes (`finance.receipts.*`) + SystemRoute permission seeds
### Frontend
- [ ] AR Receipts page (`finance.receipts.index` → `Finance/Receipts/Index.jsx`) — capture, multi-method payment lines, allocation grid
- [ ] AR ageing view + statement print/email action
### Tests
- [ ] Feature tests: receipt split across two invoices allocates correctly; over-allocation beyond receipt total rejected
- [ ] Unit tests: ageing bucket assignment around terms boundaries
- [ ] Functional pass: receive one EFT against three invoices (one partial), print the statement, verify ageing moves

## 7.4 AP Payments + Payment Run  ·  [spec](../modules/07-finance-accounts/7.4-accounts-payable.md)

### Backend
- [ ] Migration(s): `supplier_payments`, `payment_allocations`
- [ ] Models + factories; payment_number sequence (PMT-YYYYMMDD-XXXX)
- [ ] Posting: DR AP Control, CR Bank via GlPostingService
- [ ] Payment run: outstanding-invoice selection by date range/supplier → batch → bank EFT export (CSV) → post
- [ ] Form Requests + Policies; controllers + routes (`finance.payments.*`, `finance.payment-run`) + SystemRoute permission seeds
### Frontend
- [ ] AP Payments page (`finance.payments.index` → `Finance/Payments/Index.jsx`)
- [ ] Payment run screen (`finance.payment-run` → `Finance/PaymentRun/Index.jsx`) — review, select, export, post
### Tests
- [ ] Feature tests: payment run only lists posted/matched supplier invoices; posting clears AP sub-ledger
- [ ] Functional pass: run a payment batch for two suppliers, export EFT file, post, check AP ageing at zero

## 7.5 Bank Accounts + Reconciliation  ·  [spec](../modules/07-finance-accounts/7.5-cash-bank-management.md)

### Backend
- [ ] Migration(s): `bank_accounts` (linked gl_account_id, currency), `bank_statement_lines`
- [ ] Statement import (CSV/OFX) + auto-match to GL entries within `bank_rec_tolerance` setting
- [ ] Manual match, mark cleared, reconciliation report
- [ ] Form Requests + Policies; controllers + routes (`finance.bank.*`) + SystemRoute permission seeds
### Frontend
- [ ] Bank accounts page (`finance.bank.index` → `Finance/Bank/Index.jsx`)
- [ ] Reconciliation screen (`finance.bank.reconcile` → `Finance/Bank/Reconcile.jsx`) — matched/unmatched panes
### Tests
- [ ] Feature tests: import dedupes re-uploaded statement; auto-match respects tolerance
- [ ] Functional pass: import a statement, auto-match receipts, manually match a bank charge, print reconciliation

## 7.6 VAT Returns  ·  [spec](../modules/07-finance-accounts/7.6-vat-management.md)

### Backend
- [ ] Migration(s): `vat_returns`
- [ ] Return computation from posted journals (output VAT − input VAT = net), never cached values
- [ ] Status flow draft → submitted → paid; adjustments via correcting journal only
- [ ] Form Requests + Policies; controller + routes (`finance.vat.*`) + SystemRoute permission seeds
### Frontend
- [ ] VAT returns page (`finance.vat.index` → `Finance/VAT/Index.jsx`) with drill-down to source transactions
### Tests
- [ ] Feature tests: return totals reconcile to VAT Output (2210) / VAT Input (2220) movements; zero-rated sales excluded from output VAT
- [ ] Functional pass: generate a return for a month with sales + purchases, verify net VAT payable

## 7.7 Trial Balance, P&L, Balance Sheet  ·  [spec](../modules/07-finance-accounts/7.7-financial-reporting.md)

### Backend
- [ ] Report queries: trial balance at date, income statement, balance sheet — date range + branch filter, Excel/PDF export
- [ ] Routes (`finance.reports.*`) + SystemRoute permission seeds
### Frontend
- [ ] Trial balance (`finance.reports.trial-balance` → `Finance/Reports/TrialBalance.jsx`)
- [ ] P&L (`finance.reports.pl` → `Finance/Reports/PL.jsx`)
- [ ] Balance sheet (`finance.reports.balance-sheet` → `Finance/Reports/BalanceSheet.jsx`)
### Tests
- [ ] Feature tests: trial balance debits = credits; balance sheet balances (Assets = Liabilities + Equity); P&L net profit ties to retained movement
- [ ] Functional pass: after a month of seeded trading, eyeball TB, P&L and balance sheet against known figures

## Sidebar Migration  ·  [rule](../design/component-standards.md)

- [ ] Migrate deprecated `FinanceSidebar.jsx` content into a `nav.js` config rendered by the shared `ModuleLayout` sidebar (theme follows main layout — no custom styling); delete the old component
- [ ] Functional pass: finance sidebar visually identical to every other module's

## Month-End Close Screen  ·  [workflow](../workflows/month-end-close.md)

- [ ] Close checklist screen: unposted invoices, unmatched supplier invoices, bank rec status, AR/AP ageing review, depreciation journal, TB review → close period
- [ ] Feature test: close blocked while checklist items outstanding
- [ ] Functional pass: run a full month-end close on seeded data

## Deferred (Phase 6)
- [ ] Cash flow statement, gross margin by category, sales vs budget — with the financial report suite in [tasks/09-reports.md](09-reports.md)
