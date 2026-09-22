# Sub-module Sidebars: Reports & Analytics

Sub-module sidebar layouts for [Module 9: Reports & Analytics](../../modules/09-reports-analytics.md), following the universal template and hard rules in [README.md](README.md). Report-suite sidebars list each suite's individual reports as WORK items — the sidebar *is* the hub's report list.

## 9.1 Executive Dashboard

> Context: entered via Reports & Analytics › Executive Dashboard. [Spec](../../modules/09-reports-analytics/9.1-executive-dashboard.md)

```
↰ Reports & Analytics
▍ EXECUTIVE DASHBOARD  (layout-dashboard)
──────────────────────────────
WORK
  Dashboard ............... reports.dashboard               (layout-dashboard)
QUICK LINKS ⇄
  Sales Reports ........... reports.sales.index             (trending-up)
  Financial Reports ....... reports.finance.index           (banknote)
  Inventory Reports ....... reports.inventory.index         (layers)
  Workshop Reports ........ reports.workshop.index          (wrench)
  Scheduled Reports ....... reports.scheduled.index         (calendar-clock)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Single-screen sub-module: KPI tiles and charts drill through to the report suites listed in Quick Links with filters pre-applied.
- Branch scope enforced server-side; the branch selector and comparison chart show only for multi-branch users. Financial tiles are hidden for roles without AR/financial access.

## 9.2 Sales Reports

> Context: entered via Reports & Analytics › Sales Reports. [Spec](../../modules/09-reports-analytics/9.2-sales-reports.md)

```
↰ Reports & Analytics
▍ SALES REPORTS  (trending-up)
──────────────────────────────
WORK
  Daily Sales Summary ..... reports.sales.index?report=daily-summary     (calendar-days)
  Sales by Period ......... reports.sales.index?report=period            (chart-line)
  Sales by Part ........... reports.sales.index?report=part              (package)
  Sales by Category ....... reports.sales.index?report=category          (tags)
  Sales by Customer ....... reports.sales.index?report=customer          (users)
  Sales by Salesperson .... reports.sales.index?report=salesperson       (user-check)
  Quotation Conversion .... reports.sales.index?report=quote-conversion  (percent)
  Back-order Report ....... reports.sales.index?report=back-orders       (clock-alert)
  Returns Analysis ........ reports.sales.index?report=returns           (undo-2)
QUICK LINKS ⇄
  Executive Dashboard ..... reports.dashboard               (layout-dashboard)
  Customer Reports ........ reports.customers.index         (users)
  Inventory Reports ....... reports.inventory.index         (layers)
  Scheduled Reports ....... reports.scheduled.index         (calendar-clock)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Permission-filtered per report: cashiers see only their own shift's Daily Sales Summary; the rest of the list is hidden for them.
- Cost/margin columns (and margin in exports) render only for roles with the margin permission — the sidebar items are the same either way.

## 9.3 Inventory Reports

> Context: entered via Reports & Analytics › Inventory Reports. [Spec](../../modules/09-reports-analytics/9.3-inventory-reports.md)

```
↰ Reports & Analytics
▍ INVENTORY REPORTS  (layers)
──────────────────────────────
WORK
  Stock on Hand ........... reports.inventory.index?report=stock-on-hand (layers)
  Reorder Report .......... reports.inventory.index?report=reorder       (shopping-cart)
  Stock Ageing ............ reports.inventory.index?report=ageing        (hourglass)
  Dead Stock .............. reports.inventory.index?report=dead-stock    (archive)
  ABC Analysis ............ reports.inventory.index?report=abc           (bar-chart-3)
  Stock Movement .......... reports.inventory.index?report=movement      (arrow-left-right)
  Stock Value ............. reports.inventory.index?report=value         (banknote)
  Stock Take Variance ..... reports.inventory.index?report=variance      (scale)
  Inter-branch Transfer ... reports.inventory.index?report=transfers     (truck)
QUICK LINKS ⇄
  Sales Reports ........... reports.sales.index             (trending-up)
  Supplier Reports ........ reports.suppliers.index         (factory)
  Financial Reports ....... reports.finance.index           (banknote)
  Scheduled Reports ....... reports.scheduled.index         (calendar-clock)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The Reorder Report is actionable — ticked lines become draft purchase requisitions (Purchasing Officer/Buyer permission); other reports are read-only.
- Permission-filtered per report: warehouse roles get Stock on Hand / Movement / Transfer / Variance (cost columns hidden), accountants get Stock Value and Variance.

## 9.4 Financial Reports

> Context: entered via Reports & Analytics › Financial Reports. [Spec](../../modules/09-reports-analytics/9.4-financial-reports.md)

```
↰ Reports & Analytics
▍ FINANCIAL REPORTS  (banknote)
──────────────────────────────
WORK
  Trial Balance ........... reports.finance.index?report=trial-balance    (scale)
  Income Statement ........ reports.finance.index?report=income-statement (chart-line)
  Balance Sheet ........... reports.finance.index?report=balance-sheet    (landmark)
  Cash Flow ............... reports.finance.index?report=cash-flow        (waves)
  AR Ageing ............... reports.finance.index?report=ar-ageing        (user-minus)
  AP Ageing ............... reports.finance.index?report=ap-ageing        (factory)
  VAT Summary ............. reports.finance.index?report=vat-summary      (receipt)
  Bank Reconciliation ..... reports.finance.index?report=bank-rec         (check-check)
QUICK LINKS ⇄
  Executive Dashboard ..... reports.dashboard               (layout-dashboard)
  Customer Reports ........ reports.customers.index         (users)
  Supplier Reports ........ reports.suppliers.index         (factory)
  Scheduled Reports ....... reports.scheduled.index         (calendar-clock)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Access is per-report, not module-wide: an Accounts Clerk sees AR/AP Ageing and VAT Summary only; a Branch Manager sees own-branch P&L and AR Ageing; empty menus are omitted entirely.
- Reports run mid-period render with a "provisional — period not closed" watermark; closed-period figures are immutable.

## 9.5 Customer Reports

> Context: entered via Reports & Analytics › Customer Reports. [Spec](../../modules/09-reports-analytics/9.5-customer-reports.md)

```
↰ Reports & Analytics
▍ CUSTOMER REPORTS  (users)
──────────────────────────────
WORK
  Customer Ageing ......... reports.customers.index?report=ageing        (user-minus)
  Top Customers ........... reports.customers.index?report=top           (trophy)
  Purchase History ........ reports.customers.index?report=history       (history)
  Dormant Customers ....... reports.customers.index?report=dormant       (user-x)
  New Customer Report ..... reports.customers.index?report=new           (user-plus)
  Loyalty Points .......... reports.customers.index?report=loyalty       (gift)
  Credit Limit Review ..... reports.customers.index?report=credit-review (shield-alert)
QUICK LINKS ⇄
  Financial Reports ....... reports.finance.index           (banknote)
  Sales Reports ........... reports.sales.index             (trending-up)
  Scheduled Reports ....... reports.scheduled.index         (calendar-clock)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Permission-filtered per report: counter staff get Purchase History lookup only (no ageing); accounts roles get Ageing, Credit Limit Review, and History; "rank by margin" hides without the margin permission.
- Credit Limit Review recommendations are advisory — actual limit changes happen in the Customers module.

## 9.6 Supplier Reports

> Context: entered via Reports & Analytics › Supplier Reports. [Spec](../../modules/09-reports-analytics/9.6-supplier-reports.md)

```
↰ Reports & Analytics
▍ SUPPLIER REPORTS  (factory)
──────────────────────────────
WORK
  Spend Report ............ reports.suppliers.index?report=spend       (banknote)
  Performance Scorecard ... reports.suppliers.index?report=scorecard   (gauge)
  PO Status ............... reports.suppliers.index?report=po-status   (clipboard-list)
  Landed Cost Analysis .... reports.suppliers.index?report=landed-cost (ship)
  Price List Comparison ... reports.suppliers.index?report=price-comparison (scale)
QUICK LINKS ⇄
  Inventory Reports ....... reports.inventory.index         (layers)
  Financial Reports ....... reports.finance.index           (banknote)
  Scheduled Reports ....... reports.scheduled.index         (calendar-clock)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Permission-filtered per report: warehouse staff see PO Status only (expected deliveries); accountants get Spend and Landed Cost; the full suite belongs to buyers and management.
- Overdue POs sort to the top of PO Status; closing a PO short is a Purchasing-module action, not done here.

## 9.7 Workshop Reports

> Context: entered via Reports & Analytics › Workshop Reports. [Spec](../../modules/09-reports-analytics/9.7-workshop-reports.md)

```
↰ Reports & Analytics
▍ WORKSHOP REPORTS  (wrench)
──────────────────────────────
WORK
  Workshop Productivity ... reports.workshop.index?report=productivity  (activity)
  Technician Efficiency ... reports.workshop.index?report=efficiency    (gauge)
  Job Profitability ....... reports.workshop.index?report=profitability (banknote)
  Comeback Rate ........... reports.workshop.index?report=comebacks     (rotate-ccw)
  Outstanding Job Cards ... reports.workshop.index?report=outstanding   (clock-alert)
  Parts Usage ............. reports.workshop.index?report=parts-usage   (package)
  Warranty Claims ......... reports.workshop.index?report=warranty      (shield-check)
QUICK LINKS ⇄
  Executive Dashboard ..... reports.dashboard               (layout-dashboard)
  Inventory Reports ....... reports.inventory.index         (layers)
  Sales Reports ........... reports.sales.index             (trending-up)
  Scheduled Reports ....... reports.scheduled.index         (calendar-clock)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The whole sub-module hides for branches without `is_workshop`; technicians opening Technician Efficiency see only their own row.
- Accountant scope is Job Profitability and Warranty Claims; the operational reports belong to foreman and management.

## 9.8 Scheduled Reports

> Context: entered via Reports & Analytics › Scheduled Reports. [Spec](../../modules/09-reports-analytics/9.8-scheduled-reports.md)

```
↰ Reports & Analytics
▍ SCHEDULED REPORTS  (calendar-clock)
──────────────────────────────
WORK
  All Schedules ........... reports.scheduled.index         (list)
  New Schedule ............ reports.scheduled.create        (plus-circle)
QUICK LINKS ⇄
  Sales Reports ........... reports.sales.index             (trending-up)
  Inventory Reports ....... reports.inventory.index         (layers)
  Financial Reports ....... reports.finance.index           (banknote)
  Customer Reports ........ reports.customers.index         (users)
  Sys Admin: Email Config . admin.email.index               (mail)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Run history and "Run now" are row-level actions on All Schedules; failed last runs highlight in the list rather than badging the sidebar (no live count defined).
- Users can only schedule reports they can themselves view, at their own branch scope — New Schedule's report picker is permission-filtered accordingly.
- The Email Configuration quick link is cross-module (transport and template the deliveries ride on); it renders only for admin roles.
