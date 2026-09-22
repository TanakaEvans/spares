# Module 7: Finance & Accounts

> Full double-entry bookkeeping: General Ledger, Accounts Receivable, Accounts Payable, Cash & Bank Management, VAT, and financial reporting. All other modules flow financial data into this module.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 7.1 | [Chart of Accounts](#71-chart-of-accounts) | COA management |
| 7.2 | [General Ledger](#72-general-ledger) | Journal entries, period management |
| 7.3 | [Accounts Receivable (AR)](#73-accounts-receivable-ar) | Customer invoices, receipts, allocations |
| 7.4 | [Accounts Payable (AP)](#74-accounts-payable-ap) | Supplier invoices, payment runs |
| 7.5 | [Cash & Bank Management](#75-cash--bank-management) | Bank accounts, cash books, reconciliation |
| 7.6 | [VAT Management](#76-vat-management) | VAT returns, input/output tax |
| 7.7 | [Financial Reporting](#77-financial-reporting) | P&L, balance sheet, trial balance |
| 7.8 | [Period Management](#78-period-management) | Open/close financial periods |

---

## Posting Architecture

Every business transaction that has a financial impact creates GL journal entries automatically:

| Transaction | DR | CR |
|-------------|----|----|
| Customer invoice (cash sale) | Cash / Bank | Sales Revenue |
| Customer invoice (credit sale) | AR Control | Sales Revenue |
| VAT on sale | — | VAT Output |
| Customer payment | Cash / Bank | AR Control |
| GRN received | Inventory Asset | AP Control / Accruals |
| Supplier invoice matched | AP Accruals | AP Control |
| Supplier payment | AP Control | Bank |
| Stock adjustment (write-off) | Stock Write-off Expense | Inventory Asset |
| COGS on sale | Cost of Goods Sold | Inventory Asset |

---

## 7.1 Chart of Accounts

### Account Types and Typical Spares Business COA

```
ASSETS
  1000  Current Assets
    1100  Cash & Cash Equivalents
      1110    Till / Petty Cash
      1120    Bank Account - Main
      1130    Bank Account - Savings
    1200  Accounts Receivable
      1210    Trade Debtors Control
      1220    Allowance for Bad Debts
    1300  Inventory
      1310    Finished Goods Inventory
      1320    Parts on Order (in transit)
    1400  Prepayments & Other Current Assets

  1500  Non-Current Assets
    1510    Property, Plant & Equipment
    1520    Accumulated Depreciation
    1530    Vehicles

LIABILITIES
  2000  Current Liabilities
    2100  Accounts Payable
      2110    Trade Creditors Control
      2120    Accrued Liabilities
    2200  VAT Payable
      2210    VAT Output
      2220    VAT Input
      2230    VAT Control (Output - Input)
    2300  Lay-by Deposits (deferred revenue)
    2400  Other Current Liabilities

  2500  Long-term Liabilities
    2510    Bank Loan

EQUITY
  3000  Owner's Equity
    3100    Share Capital
    3200    Retained Earnings
    3300    Current Year Profit/Loss

REVENUE
  4000  Sales Revenue
    4100    Sales - Parts (Retail)
    4200    Sales - Parts (Trade)
    4300    Sales - Labour (Workshop)
    4400    Sales - Oils & Lubricants
    4900    Sales Returns & Allowances

COST OF GOODS SOLD
  5000  Cost of Goods Sold
    5100    Cost of Parts Sold
    5200    Cost of Labour (Internal)
    5300    Stock Write-offs
    5400    Supplier Rebates (contra)

OPERATING EXPENSES
  6000  Salaries & Wages
  6100  Rent & Occupancy
  6200  Motor Vehicle Expenses
  6300  Telephone & Internet
  6400  Advertising & Marketing
  6500  Bank Charges
  6600  Depreciation
  6700  Insurance
  6800  Repairs & Maintenance
  6900  Stationery & Printing
  7000  Other Operating Expenses
```

### `gl_accounts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| account_code | varchar(20) | e.g. "1210" |
| name | varchar(255) | |
| type | varchar(20) | `asset`, `liability`, `equity`, `revenue`, `expense` |
| category | varchar(50) | e.g. "current_asset", "revenue_sales" |
| is_control_account | boolean | AR, AP, Inventory control accounts |
| control_type | varchar(30) | `debtors`, `creditors`, `inventory` |
| normal_balance | varchar(10) | `debit` or `credit` |
| is_active | boolean | |
| allow_direct_posting | boolean | False for control accounts |
| notes | text | |

---

## 7.2 General Ledger

### Financial Periods
```
Financial Year (e.g. 2026/01/01 – 2026/12/31)
  ├── Period 01: January 2026
  ├── Period 02: February 2026
  ├── ...
  └── Period 12: December 2026
```

### `gl_years`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(20) | "FY2026" |
| start_date | date | |
| end_date | date | |
| status | varchar(20) | `open`, `closed`, `locked` |

### `gl_periods`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| year_id | bigint FK | |
| period_number | int | 1–12 |
| name | varchar(20) | "Jan 2026" |
| start_date | date | |
| end_date | date | |
| status | varchar(20) | `open`, `closed`, `locked` |
| closed_by | bigint FK → users | |
| closed_at | timestamp | |

### `gl_journals`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| journal_number | varchar(20) | JNL-YYYYMMDD-XXXX |
| journal_type | varchar(30) | `sales`, `purchase`, `cash_receipt`, `bank`, `general`, `opening` |
| period_id | bigint FK | |
| journal_date | date | |
| description | varchar(255) | |
| reference | varchar(100) | Invoice/GRN/etc. number |
| source_type | varchar(50) | `SalesDocument`, `SupplierInvoice`, etc. |
| source_id | bigint | |
| status | varchar(20) | `draft`, `posted`, `reversed` |
| posted_by | bigint FK → users | |
| posted_at | timestamp | |
| reversed_by | bigint FK → users | |
| reversed_journal_id | bigint FK | |

### `gl_journal_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| journal_id | bigint FK | |
| account_id | bigint FK | |
| description | varchar(255) | |
| debit | decimal(15,2) | 0.00 if credit entry |
| credit | decimal(15,2) | 0.00 if debit entry |
| reference | varchar(100) | |

**Constraint**: For every posted journal, `SUM(debit) = SUM(credit)`. This is enforced at the service layer.

### Business Rules
1. Journals in a closed or locked period cannot be posted. The system will suggest re-dating to the current open period.
2. Reversals create a new journal (not a modification) with opposite debits/credits.
3. Control accounts (`is_control_account = true`) cannot be posted to via manual journals — only through sub-ledger posting.

---

## 7.3 Accounts Receivable (AR)

### Purpose
Tracks money owed to the business by customers. AR is the sub-ledger for the Trade Debtors Control account.

### AR Aging
The standard aging buckets:
- **Current**: Invoice date within payment terms
- **30 days**: 1–30 days past due
- **60 days**: 31–60 days past due
- **90+ days**: 61+ days past due

### `customer_receipts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| receipt_number | varchar(20) | RCP-YYYYMMDD-XXXX |
| customer_id | bigint FK | |
| branch_id | bigint FK | |
| receipt_date | date | |
| total_amount | decimal(15,2) | |
| reference | varchar(100) | Bank ref / deposit slip |
| notes | text | |
| status | varchar(20) | `draft`, `posted` |
| posted_by | bigint FK → users | |
| gl_journal_id | bigint FK | |

### `customer_receipt_payment_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| receipt_id | bigint FK | |
| payment_method_id | bigint FK | |
| amount | decimal(15,2) | |
| reference | varchar(100) | Card auth / EFT ref |

### `receipt_allocations`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| receipt_id | bigint FK | |
| invoice_id | bigint FK | → sales_documents |
| amount_applied | decimal(15,2) | |

### Business Rules
1. A receipt can be split across multiple invoices (partial payment, multiple invoices in one EFT).
2. Unallocated receipts appear on the AR ageing as "Unapplied Credits" — must be allocated within 5 business days.
3. Posting a receipt creates: DR Bank, CR AR Control.

---

## 7.4 Accounts Payable (AP)

### Purpose
Tracks money owed to suppliers. AP is the sub-ledger for the Trade Creditors Control account.

### `supplier_payments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| payment_number | varchar(20) | PMT-YYYYMMDD-XXXX |
| supplier_id | bigint FK | |
| bank_account_id | bigint FK | Paying from |
| payment_date | date | |
| total_amount | decimal(15,2) | |
| reference | varchar(100) | EFT batch ref |
| notes | text | |
| status | varchar(20) | `draft`, `posted` |
| gl_journal_id | bigint FK | |

### `payment_allocations`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| payment_id | bigint FK | |
| invoice_id | bigint FK | → supplier_invoices |
| amount_applied | decimal(15,2) | |

### Payment Run
Bulk payment processing:
1. Select date range and supplier(s)
2. System lists all outstanding invoices
3. Accountant reviews and selects invoices to pay
4. System generates a payment batch
5. Export to bank EFT format (bank-specific CSV/XML)
6. Post payments — creates AP journal entries

---

## 7.5 Cash & Bank Management

### `bank_accounts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | "Main Cheque Account" |
| bank_name | varchar(100) | |
| account_number | varchar(30) | |
| branch_code | varchar(20) | |
| currency_id | bigint FK | |
| gl_account_id | bigint FK | Linked GL account |
| is_active | boolean | |
| opening_balance | decimal(15,2) | |
| current_balance | decimal(15,2) | From GL |

### Bank Reconciliation
```
Import bank statement (CSV/OFX) → 
Auto-match transactions to GL entries → 
Manually match unmatched items → 
Mark cleared → 
Print reconciliation report
```

### `bank_statement_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| bank_account_id | bigint FK | |
| statement_date | date | |
| description | varchar(255) | |
| reference | varchar(100) | |
| debit | decimal(15,2) | |
| credit | decimal(15,2) | |
| balance | decimal(15,2) | |
| is_reconciled | boolean | |
| gl_journal_line_id | bigint FK | Matched GL entry |
| imported_at | timestamp | |

---

## 7.6 VAT Management

### VAT Rates
| Code | Rate | Description |
|------|------|-------------|
| `STD` | 15% | Standard rate (typical in Southern Africa) |
| `ZERO` | 0% | Zero-rated (basic foodstuffs, exports) |
| `EXEMPT` | n/a | Exempt supplies |

### `vat_returns`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| period_start | date | |
| period_end | date | |
| output_vat | decimal(15,2) | VAT on sales |
| input_vat | decimal(15,2) | VAT on purchases |
| net_vat | decimal(15,2) | output - input (payable to tax authority) |
| status | varchar(20) | `draft`, `submitted`, `paid` |
| submitted_at | timestamp | |
| paid_at | timestamp | |
| notes | text | |

### Business Rules
1. VAT is calculated on every transaction at the point of posting — not stored as a cached value.
2. Adjustments to VAT returns require a correcting journal with full audit trail.
3. Zero-rated customers (e.g., exporters) must provide a valid exemption certificate on file.

---

## 7.7 Financial Reporting

### Standard Reports

| Report | Description |
|--------|-------------|
| Trial Balance | All account balances at a date |
| Income Statement (P&L) | Revenue, COGS, gross profit, expenses, net profit |
| Balance Sheet | Assets, liabilities, equity at a date |
| Cash Flow Statement | Cash movements classified operating/investing/financing |
| AR Ageing | Customer balances by age bracket |
| AP Ageing | Supplier balances by age bracket |
| VAT Summary | Input/output VAT for a period |
| Bank Reconciliation | Cleared vs uncleared items |
| Gross Margin by Category | Sales vs COGS by parts category |
| Sales vs Budget | Actual vs budget by account |

All reports support:
- Date range selection
- Branch filter
- Export to Excel / PDF
- Print layout (formatted)

---

## 7.8 Period Management

### Workflow
```
Month End:
1. Ensure all sales invoices for the period are posted
2. Ensure all supplier invoices are matched
3. Run bank reconciliation
4. Run AR / AP ageing — resolve queries
5. Post depreciation journal (manual or automated)
6. Review trial balance
7. Close period (status → 'closed') — no more posting to this period
8. Open next period

Year End:
1. Post year-end adjustments
2. Run full financial reports
3. Lock the financial year
4. Roll over opening balances to new year
```

---

## Pages to Build

| Page | Route | Component |
|------|-------|-----------|
| COA | `finance.coa.index` | `Finance/COA/Index.jsx` |
| GL Enquiry | `finance.gl.index` | `Finance/GL/Index.jsx` |
| Journals | `finance.journals.index` | `Finance/Journals/Index.jsx` |
| AR Receipts | `finance.receipts.index` | `Finance/Receipts/Index.jsx` |
| AP Payments | `finance.payments.index` | `Finance/Payments/Index.jsx` |
| Payment run | `finance.payment-run` | `Finance/PaymentRun/Index.jsx` |
| Bank accounts | `finance.bank.index` | `Finance/Bank/Index.jsx` |
| Bank reconciliation | `finance.bank.reconcile` | `Finance/Bank/Reconcile.jsx` |
| VAT returns | `finance.vat.index` | `Finance/VAT/Index.jsx` |
| Trial balance | `finance.reports.trial-balance` | `Finance/Reports/TrialBalance.jsx` |
| P&L | `finance.reports.pl` | `Finance/Reports/PL.jsx` |
| Balance sheet | `finance.reports.balance-sheet` | `Finance/Reports/BalanceSheet.jsx` |

---

## Integration Points

| Module | Relationship |
|--------|-------------|
| **Sales** | Invoice → AR + revenue + VAT output journals |
| **Purchasing** | GRN → inventory, Supplier invoice → AP |
| **Inventory** | Stock adjustments → inventory/write-off journals |
| **Workshop** | Job invoice flows through Sales → Finance |
| **Customers** | AR balance, receipts |
| **Suppliers** | AP balance, payments |
