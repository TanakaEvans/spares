# Module 9: Reports & Analytics

> Business intelligence across all modules. Pre-built operational reports for day-to-day management, analytics dashboards for strategic decisions, and scheduled report distribution.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 9.1 | [Executive Dashboard](#91-executive-dashboard) | Real-time KPI summary for owners/managers |
| 9.2 | [Sales Reports](#92-sales-reports) | Sales performance across all dimensions |
| 9.3 | [Inventory Reports](#93-inventory-reports) | Stock health, ageing, movement |
| 9.4 | [Financial Reports](#94-financial-reports) | P&L, cash flow, AR/AP ageing |
| 9.5 | [Customer Reports](#95-customer-reports) | Customer activity, loyalty, debt |
| 9.6 | [Supplier Reports](#96-supplier-reports) | Spend, performance, delivery |
| 9.7 | [Workshop Reports](#97-workshop-reports) | Productivity, efficiency, costing |
| 9.8 | [Scheduled Reports](#98-scheduled-reports) | Automated email delivery |

---

## 9.1 Executive Dashboard

### Purpose
The first screen an owner or branch manager sees every morning. Shows the business pulse without needing to drill into individual modules.

### KPI Tiles (Top Row)
| KPI | Description | Period |
|-----|-------------|--------|
| Today's Sales | Total revenue invoiced today | Today |
| Yesterday's Sales | For comparison | Yesterday |
| Month-to-Date Sales | Running MTD | Current month |
| Month-to-Date vs Target | % against sales budget | Current month |
| Outstanding AR | Total unpaid customer invoices | Now |
| Overdue AR (60+) | High-risk debtors | Now |
| Stock Value | Total inventory at cost | Now |
| Open Job Cards | Workshop jobs in progress | Now |

### Charts
- **Sales trend**: 12-month bar chart (this year vs last year)
- **Top 10 parts by sales value**: horizontal bar
- **Sales by category**: pie/donut
- **AR ageing profile**: stacked bar (current/30/60/90+)
- **Branch performance comparison**: if multi-branch

### Business Rules
1. KPI figures are calculated in real-time (not pre-aggregated caches) for accuracy.
2. Dashboard respects branch permissions — branch manager only sees their branch data.
3. "Month-to-date vs Target" requires a sales budget to be configured in the system.

---

## 9.2 Sales Reports

### Report List

#### Daily Sales Summary
- Total invoices by payment method (cash / card / account)
- Number of transactions
- Average transaction value
- Top 5 parts sold
- Cashier breakdown
- Tax (VAT) collected

#### Sales by Period
- Daily / Weekly / Monthly / Quarterly / Yearly
- Filters: branch, salesperson, customer, customer group, category
- Columns: qty sold, revenue excl VAT, VAT, revenue incl VAT, cost, gross margin, margin %

#### Sales by Part
- Which parts sold the most (qty and value)
- Includes: on-hand stock, last sale date, sell-through rate
- Useful for buying decisions

#### Sales by Category
- Revenue and margin broken down by parts category
- Drill-down to subcategory

#### Sales by Customer
- Customer purchase history summary
- Columns: transactions, total spend, avg order value, last purchase date
- Sort by spend descending → top customer list

#### Sales by Salesperson / Cashier
- Revenue, margin, transaction count per person
- Shift performance summary

#### Quotation Conversion Report
- Quotes issued vs converted to orders/invoices
- Win rate by salesperson
- Lost quote analysis (reason codes)

#### Back-order Report
- Lines on back-order with customer, required date, supplier ETA
- Sorted by oldest back-order first

#### Returns Analysis
- Credit notes issued: by reason, by salesperson, by part
- Return rate % by category
- Financial impact of returns

---

## 9.3 Inventory Reports

### Report List

#### Stock on Hand
- All parts with current qty on hand, qty reserved, qty available
- Filter by: branch, category, brand, status
- Flag: below reorder level, out of stock, negative (if allowed)
- Export to Excel for buying review

#### Reorder Report
- Parts currently below their reorder point
- Columns: part, branch, on hand, reorder level, qty to order, preferred supplier, last order date
- One-click create purchase requisitions from this report

#### Stock Ageing Report
- How long stock has been sitting
- Brackets: 0–30 days, 31–60, 61–90, 91–180, 180+ days
- Flags dead stock (no movement in 6/12 months)
- Value per bracket (helps identify cash tied up in slow stock)

#### Dead Stock Report
- Parts with zero movement in last 12 months
- Total value
- Recommendation: return to supplier, mark down price, write off

#### ABC Analysis
- Classify all parts: A (top 20% by sales value), B (next 30%), C (bottom 50%)
- Helps focus buying effort on high-velocity parts

#### Stock Movement Report
- Full ledger for a part or date range
- Every IN and OUT transaction with reference
- Useful for investigating discrepancies

#### Stock Value Report
- Total inventory value at cost (FIFO/AVCO)
- Breakdown by category, branch
- Reconciles to the Inventory GL account

#### Stock Take Variance Report
- Results of completed stock takes
- Variance qty and value per part
- Signed off by who

#### Inter-branch Transfer Report
- Transfers between branches in a period
- Status: pending / in transit / received

---

## 9.4 Financial Reports

See [Module 7: Finance & Accounts](07-finance-accounts.md) for detailed financial report descriptions.

Quick reference:

| Report | Description |
|--------|-------------|
| Trial Balance | All GL accounts with debit/credit totals |
| Income Statement | Revenue − COGS = Gross Profit − Expenses = Net Profit |
| Balance Sheet | Assets = Liabilities + Equity |
| Cash Flow | Operating, investing, financing activities |
| AR Ageing | Customer debt by age bracket |
| AP Ageing | Supplier liability by age bracket |
| VAT Summary | Output vs input VAT per period |
| Bank Reconciliation | Cleared vs outstanding bank items |

---

## 9.5 Customer Reports

### Report List

#### Customer Ageing (Debtors)
- All trade customers with balances
- Aged by: current / 30 / 60 / 90+ days
- Flags high-risk accounts
- Used for collections management

#### Top Customers
- Ranked by: total spend, number of visits, gross margin contribution
- Period: MTD, YTD, last 12 months

#### Customer Purchase History
- All transactions for one customer
- Invoice list with status and payment
- Useful for customer service calls

#### Dormant Customers
- Customers with no purchase in the last X days (configurable: 60/90/180)
- Re-engagement opportunity list

#### New Customer Report
- Customers first registered in the period
- With their first purchase details

#### Loyalty Points Report
- Current points balances
- Points expiring in next 30/60 days
- Points earned vs redeemed in period

#### Credit Limit Review
- Customers approaching/exceeding credit limit
- Customers on hold
- Recommended limit adjustments based on payment history

---

## 9.6 Supplier Reports

### Report List

#### Supplier Spend Report
- Total purchasing spend per supplier in a period
- Useful for rebate negotiations and vendor rationalisation

#### Supplier Performance Scorecard
- On-time delivery rate
- Fill rate
- Return/rejection rate
- Invoice accuracy
- Trend over 12 months

#### Purchase Order Status
- All open POs with expected delivery dates
- Overdue POs (delivery date passed, not fully received)

#### Landed Cost Analysis
- Total cost per shipment (product cost + freight + customs + clearing)
- Impact on unit cost vs original PO cost

#### Supplier Price List Comparison
- Same part across multiple suppliers: cost, lead time, MOQ
- Supports buying decisions

---

## 9.7 Workshop Reports

### Report List

#### Workshop Productivity Report
- Job cards opened / completed / invoiced in period
- Revenue from workshop
- Average job value

#### Technician Efficiency Report
- Flat rate hours billed vs actual hours worked
- Efficiency % per technician
- Utilisation % (productive time / available time)

#### Job Profitability Report
- For each job: parts cost, labour cost, billed amount, gross margin

#### Comeback Rate Report
- Jobs where vehicle returned within 30 days for same fault
- By technician — identifies quality issues

#### Outstanding Job Cards
- Jobs open > X days without completion
- Sorted by oldest first

#### Parts Usage in Workshop
- Which parts are most commonly used in workshop
- Useful for workshop-specific stock levels

#### Warranty Claims Report
- Claims submitted, approved, rejected, pending
- Value recovered vs claimed
- By supplier

---

## 9.8 Scheduled Reports

### Purpose
Automatically generate and email reports on a schedule. No manual action required.

### `scheduled_reports`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | "Daily Sales Summary" |
| report_key | varchar(50) | Internal report identifier |
| frequency | varchar(20) | `daily`, `weekly`, `monthly` |
| run_time | time | e.g. "07:00" |
| run_day | int | 1–31 for monthly, 1–7 for weekly |
| parameters | json | Date range, filters |
| recipients | json | Array of email addresses |
| format | varchar(10) | `pdf`, `excel` |
| is_active | boolean | |
| last_run_at | timestamp | |

### Default Scheduled Reports
| Report | Frequency | Default Recipients |
|--------|-----------|-------------------|
| Daily Sales Summary | Daily 08:00 | Branch managers |
| Weekly Stock Reorder | Monday 07:00 | Buyer / purchasing |
| Monthly AR Ageing | 1st of month | Accounts, Owner |
| Monthly AP Ageing | 1st of month | Accounts |
| Monthly P&L | 3rd of month | Owner, Accounts |
| Top Customers (MTD) | 1st of month | Sales manager |
| Dormant Customer List | Monthly | Sales |

---

## Report Architecture

### Implementation Notes

**All reports** should be implemented as:
- Laravel controller action returning Inertia page with data
- Client-side filtering/sorting where dataset is < 10,000 rows
- Server-side pagination for large datasets
- Excel export via `maatwebsite/laravel-excel`
- PDF export via `barryvdh/laravel-dompdf`
- Configurable date ranges
- Branch-level access control

**Dashboard charts** use:
- React + a charting library (recommend **Recharts** — MIT licence, React-native, good DX)
- Data fetched server-side and passed as Inertia props
- Refresh on page focus (Inertia partial reload)

---

## Pages to Build

| Page | Route | Component |
|------|-------|-----------|
| Executive dashboard | `reports.dashboard` | `Reports/Dashboard.jsx` |
| Sales reports | `reports.sales.index` | `Reports/Sales/Index.jsx` |
| Inventory reports | `reports.inventory.index` | `Reports/Inventory/Index.jsx` |
| Financial reports | `reports.finance.index` | `Reports/Finance/Index.jsx` |
| Customer reports | `reports.customers.index` | `Reports/Customers/Index.jsx` |
| Supplier reports | `reports.suppliers.index` | `Reports/Suppliers/Index.jsx` |
| Workshop reports | `reports.workshop.index` | `Reports/Workshop/Index.jsx` |
| Scheduled reports | `reports.scheduled.index` | `Reports/Scheduled/Index.jsx` |
