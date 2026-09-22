# Module 5: Customer Management (CRM)

> Manages the full customer relationship: profiles, trade accounts, credit, vehicle registrations, loyalty points, and communication history.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 5.1 | [Customer Profiles](#51-customer-profiles) | Individual and business customer master data |
| 5.2 | [Trade Accounts](#52-trade-accounts) | Credit accounts for mechanics, garages, fleets |
| 5.3 | [Credit Management](#53-credit-management) | Limits, ageing, holds |
| 5.4 | [Customer Vehicles](#54-customer-vehicles) | Registered vehicles per customer |
| 5.5 | [Statements](#55-statements) | Monthly account statements |
| 5.6 | [Loyalty Programme](#56-loyalty-programme) | Points and tier-based rewards |
| 5.7 | [Customer Groups](#57-customer-groups) | Segment customers by type |
| 5.8 | [Communication Log](#58-communication-log) | Calls, emails, notes per customer |

---

## 5.1 Customer Profiles

### Customer Types
| Type | Description | Common examples |
|------|-------------|----------------|
| `individual` | Retail walk-in customer | Car owner |
| `business` | Trade account | Garage, panel beater |
| `fleet` | Large fleet operator | Transport company, government |
| `dealer` | Parts reseller | Another spares shop |
| `cash` | Anonymous walk-in | No profile needed |

### Database Tables

#### `customers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| customer_number | varchar(20) | CUST-0001 |
| type | varchar(20) | See types above |
| name | varchar(255) | Full name or business name |
| trading_name | varchar(255) | DBA / trading as |
| tax_number | varchar(30) | Company registration |
| vat_number | varchar(30) | VAT registration |
| customer_group_id | bigint FK | |
| credit_limit | decimal(15,2) | 0 = cash only |
| payment_terms_id | bigint FK | |
| price_list_id | bigint FK | |
| currency_id | bigint FK | |
| balance | decimal(15,2) | Current AR balance |
| on_hold | boolean | Block new sales |
| hold_reason | varchar(255) | |
| loyalty_points | int | Current points balance |
| loyalty_tier_id | bigint FK | |
| salesperson_id | bigint FK → users | Assigned sales rep |
| branch_id | bigint FK | Home branch |
| status | varchar(20) | `active`, `inactive`, `blacklisted` |
| blacklist_reason | text | |
| notes | text | |

#### `customer_addresses`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| customer_id | bigint FK | |
| type | varchar(20) | `billing`, `delivery`, `postal` |
| line1 | varchar(255) | |
| line2 | varchar(255) | |
| suburb | varchar(100) | |
| city | varchar(100) | |
| province | varchar(100) | |
| postal_code | varchar(10) | |
| country | varchar(3) | ISO |
| is_default | boolean | |
| contact_person | varchar(100) | At this address |
| phone | varchar(20) | |

#### `customer_contacts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| customer_id | bigint FK | |
| name | varchar(100) | |
| position | varchar(100) | |
| email | varchar(255) | |
| phone | varchar(20) | |
| mobile | varchar(20) | |
| is_primary | boolean | |
| receives_statements | boolean | |
| receives_invoices | boolean | |
| receives_quotes | boolean | |

---

## 5.2 Trade Accounts

Trade accounts are business customers who purchase on credit (buy now, pay at end of month or on agreed terms).

### Key Features
- Credit limit per customer (hard or soft limit)
- Payment terms: COD, Net 30, Net 60, End of Month + 30
- Statement generation and email delivery
- Aged debt tracking (current, 30 days, 60 days, 90+ days)
- On-hold function (blocks new sales when overdue)
- Account manager assignment

### `payment_terms`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(50) | "Net 30", "COD", "EOM+30" |
| days | int | Number of days |
| type | varchar(20) | `net_days`, `cod`, `prepaid`, `end_of_month` |
| early_payment_discount_pct | decimal(5,2) | Early payment incentive |
| late_payment_penalty_pct | decimal(5,2) | Monthly penalty rate |

---

## 5.3 Credit Management

### Credit Check at POS
Every time an on-account sale is created for a trade customer, the system runs:
1. **Balance check**: current balance + new sale ≤ credit limit
2. **Ageing check**: no invoices overdue > 60 days
3. **Hold check**: customer not on manual hold

If any check fails, a supervisor override PIN is required.

### Aged Debt Analysis
| Bracket | Description |
|---------|-------------|
| Current | Within payment terms |
| 30 days | 1–30 days overdue |
| 60 days | 31–60 days overdue |
| 90+ days | 61+ days overdue |

### Business Rules
1. Customers with 90+ day debt cannot make credit sales without manager approval.
2. Credit limit increases require manager approval and documentation.
3. Auto-hold: system automatically places customer on hold when balance exceeds 110% of credit limit.

---

## 5.4 Customer Vehicles

Linked from Workshop module (see `customer_vehicles` table in [Module 4](04-workshop-management.md)). Customers can have multiple vehicles.

---

## 5.5 Statements

### Monthly Account Statement
Generated for all trade customers with a balance or movement in the period.

**Statement contains:**
- Opening balance
- All invoices issued in the period
- All credit notes issued
- All payments received
- Closing balance
- Aged analysis

**Delivery:** Email (PDF) or print. Auto-sent on the 1st of each month.

---

## 5.6 Loyalty Programme

### Purpose
Reward retail (non-trade) customers with points for purchases, redeemable against future purchases.

### Earn Rate
Default: 1 point per $1 spent (configurable per tier and product category).

### `loyalty_tiers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(50) | "Bronze", "Silver", "Gold", "Platinum" |
| min_points | int | Points needed to reach tier |
| earn_multiplier | decimal(4,2) | e.g. 1.5× for Gold |
| discount_pct | decimal(5,2) | Additional discount at this tier |
| benefits_description | text | |

### `loyalty_transactions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| customer_id | bigint FK | |
| type | varchar(20) | `earn`, `redeem`, `expire`, `adjust`, `bonus` |
| points | int | Positive = credit, negative = debit |
| value | decimal(15,2) | Dollar value of points |
| reference_type | varchar(50) | `Invoice`, `CreditNote`, etc. |
| reference_id | bigint | |
| notes | text | |

### Business Rules
1. Points are only earned on retail purchases, not on trade/credit account purchases.
2. Points expire 12 months after earning date (configurable).
3. A minimum redemption threshold applies (e.g., 500 points = $5).
4. Points cannot be earned on already-discounted promotions (double-dipping prevention).

---

## 5.7 Customer Groups

Segment customers to apply pricing and marketing rules.

### `customer_groups`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | "Retail", "Mechanics", "Fleet", "Panel Beaters" |
| description | text | |
| default_price_list_id | bigint FK | |
| default_discount_pct | decimal(5,2) | Group-level discount |
| default_credit_limit | decimal(15,2) | Starting limit for new members |
| default_payment_terms_id | bigint FK | |

---

## 5.8 Communication Log

Track every interaction with a customer.

### `customer_communications`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| customer_id | bigint FK | |
| type | varchar(20) | `call`, `email`, `visit`, `note`, `complaint` |
| subject | varchar(255) | |
| notes | text | |
| outcome | varchar(255) | |
| follow_up_required | boolean | |
| follow_up_date | date | |
| user_id | bigint FK | Who logged it |
| created_at | timestamp | |

---

## Pages to Build

| Page | Route | Component |
|------|-------|-----------|
| Customers list | `customers.index` | `Customers/Index.jsx` |
| New customer | `customers.create` | `Customers/Create.jsx` |
| Customer profile | `customers.show` | `Customers/Show.jsx` |
| Account statement | `customers.statement` | `Customers/Statement.jsx` |
| Credit management | `customers.credit` | `Customers/Credit/Index.jsx` |
| Loyalty dashboard | `customers.loyalty` | `Customers/Loyalty/Index.jsx` |
| Customer groups | `customers.groups.index` | `Customers/Groups/Index.jsx` |

---

## Integration Points

| Module | Relationship |
|--------|-------------|
| **Sales** | Customer profile auto-applies pricing, credit check on sale |
| **Workshop** | Customer's vehicles and job history |
| **Finance** | AR balance, payments, statements |
| **Inventory** | Vehicle-based fitment filter at POS |
| **Reports** | Customer analytics, top customers, ageing |
