# Sub-module Sidebars: Customer Management

Sub-module sidebars for [Module 5: Customer Management](../../modules/05-customer-management.md), built to the [universal template](README.md).

## 5.1 Customer Profiles

> Context: entered via Customers › Customer Profiles. [Spec](../../modules/05-customer-management/5.1-customer-profiles.md)

```
↰ Customers
▍ CUSTOMER PROFILES  (users)
──────────────────────────────
WORK
  All Customers ........... customers.index                 (list)
  New Customer ............ customers.create                (user-plus)
QUICK LINKS ⇄
  Credit Management ....... customers.credit                (gauge)
  Customer Groups ......... customers.groups.index          (users-round)
  Communication Log ....... customers.followups             (message-square)
  Loyalty Programme ....... customers.loyalty               (award)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The 360° profile (`customers.show`) and edit (`customers.edit`) open from the list; field-level permissions apply on edit (credit fields are 5.3's, read-only here).
- Vehicles, statements, loyalty and communications render as tabs on the profile rather than as sidebar items.

## 5.2 Trade Accounts

> Context: entered via Customers › Trade Accounts. [Spec](../../modules/05-customer-management/5.2-trade-accounts.md)

```
↰ Customers
▍ TRADE ACCOUNTS  (briefcase)
──────────────────────────────
WORK
  Accounts Dashboard ...... customers.credit                (layout-dashboard)
  Account Statement ....... customers.statement             (file-text)
QUICK LINKS ⇄
  Customer Profiles ....... customers.index                 (users)
  Statements .............. customers.statement             (file-text)
  Customer Groups ......... customers.groups.index          (users-round)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The dashboard (shared surface with 5.3) shows all trade accounts: limits, balances, ageing, holds; the single-account view is the Credit tab on `customers.show`, with application documents attached.
- Opening a trade account converts an existing profile — creation starts from the customer's profile, not from this sidebar.
- Group defaults (5.7) pre-fill limits and terms for new accounts.

## 5.3 Credit Management

> Context: entered via Customers › Credit Management. [Spec](../../modules/05-customer-management/5.3-credit-management.md)

```
↰ Customers
▍ CREDIT MANAGEMENT  (gauge)
──────────────────────────────
WORK
  Credit Dashboard ........ customers.credit                (layout-dashboard) [badge: holds & over-limit]
INSIGHTS
  Aged Debt Report ........ customers.credit (aged view)    (file-bar-chart)
QUICK LINKS ⇄
  Statements .............. customers.statement             (file-text)
  Communication Log ....... customers.followups             (message-square)
  Finance: AR Receipts .... finance.receipts.index          (banknote)
  Trade Accounts .......... customers.credit                (briefcase)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge counts accounts currently on hold plus over-limit exceptions, refreshed by the daily ageing run.
- The aged debt report is printable/exportable by customer, branch or salesperson; single-account ageing, limit history and disputes live on the profile's Credit tab.
- Ageing is built from AR transactions (7.3); collection calls and promises-to-pay are captured in the communication log (5.8).

## 5.4 Customer Vehicles

> Context: entered via Customers › Customer Vehicles. [Spec](../../modules/05-customer-management/5.4-customer-vehicles.md)

```
↰ Customers
▍ CUSTOMER VEHICLES  (car)
──────────────────────────────
WORK
  Vehicles by Customer .... customers.show (Vehicles tab)   (car)
QUICK LINKS ⇄
  Customer Profiles ....... customers.index                 (users)
  Communication Log ....... customers.followups             (message-square)
  Workshop: Vehicle Registry  workshop.vehicles.search      (car-front)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- This sub-module renders inside profile context: the Vehicles tab lists a customer's vehicles with quick actions, and vehicle detail (spec, service and purchase history, documents) opens within `customers.show`.
- The POS vehicle picker embeds in the sales flow and never swaps the sidebar.
- Workshop jobs, odometer capture and service history write through from Module 4.

## 5.5 Statements

> Context: entered via Customers › Statements. [Spec](../../modules/05-customer-management/5.5-statements.md)

```
↰ Customers
▍ STATEMENTS  (file-text)
──────────────────────────────
WORK
  Statement Run ........... customers.credit (run dashboard) (send)         [badge: delivery exceptions]
  Account Statement ....... customers.statement             (file-text)
QUICK LINKS ⇄
  Trade Accounts .......... customers.credit                (briefcase)
  Credit Management ....... customers.credit                (gauge)
  Communication Log ....... customers.followups             (message-square)
  Finance: AR Receipts .... finance.receipts.index          (banknote)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge counts unresolved delivery failures/exceptions from the latest batch run; failures also log to 5.8 for follow-up.
- `customers.statement` views/prints/emails a statement for any customer + period; the per-customer archive is the Statements tab on `customers.show`.
- Statement transactions and ageing come from AR (7.3) and credit management (5.3).

## 5.6 Loyalty Programme

> Context: entered via Customers › Loyalty Programme. [Spec](../../modules/05-customer-management/5.6-loyalty-programme.md)

```
↰ Customers
▍ LOYALTY PROGRAMME  (award)
──────────────────────────────
WORK
  Loyalty Dashboard ....... customers.loyalty               (layout-dashboard)
SETUP
  Tier Configuration ...... customers.loyalty (tiers)       (settings-2)
QUICK LINKS ⇄
  Customer Profiles ....... customers.index                 (users)
  Customer Groups ......... customers.groups.index          (users-round)
  Finance: Reporting ...... finance.reports.pl              (bar-chart-3)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Tier Configuration (tiers, earn rates, category rules) is permission-gated to programme administrators; SETUP hides for everyone else.
- Member balance/tier/history is the Loyalty tab on `customers.show`; the redemption dialog lives inside the POS flow.
- Points-liability provision reports through Financial Reporting (7.7); eligibility is gated by customer group (5.7).

## 5.7 Customer Groups

> Context: entered via Customers › Customer Groups. [Spec](../../modules/05-customer-management/5.7-customer-groups.md)

```
↰ Customers
▍ CUSTOMER GROUPS  (users-round)
──────────────────────────────
WORK
  All Groups .............. customers.groups.index          (list)
  Bulk Reassignment ....... customers.groups.index (bulk)   (arrow-right-left)
QUICK LINKS ⇄
  Customer Profiles ....... customers.index                 (users)
  Trade Accounts .......... customers.credit                (briefcase)
  Loyalty Programme ....... customers.loyalty               (award)
  Credit Management ....... customers.credit                (gauge)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Group detail/edit (defaults, member list, change history) opens within the index.
- Group-driven limit changes still route through 5.3's approval thresholds; per-customer overrides remain on the profile.

## 5.8 Communication Log

> Context: entered via Customers › Communication Log. [Spec](../../modules/05-customer-management/5.8-communication-log.md)

```
↰ Customers
▍ COMMUNICATION LOG  (message-square)
──────────────────────────────
WORK
  My Follow-ups ........... customers.followups             (list-todo)     [badge: due & overdue]
  Complaints .............. customers.index (complaints)    (message-square-warning)
QUICK LINKS ⇄
  Customer Profiles ....... customers.index                 (users)
  Credit Management ....... customers.credit                (gauge)
  Statements .............. customers.statement             (file-text)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Badge is per-user: the signed-in user's due and overdue follow-ups (also surfaced as a dashboard widget).
- The full interaction timeline is the Communications tab on `customers.show`; the complaints view is the customer index filtered to `complaint` entries by category/severity.
- Entries can link to a specific vehicle (5.4); collection activity and statement delivery failures land here from 5.3/5.5.
