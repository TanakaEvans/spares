# Tasks: Customer Management

> Phase 3 of the [implementation plan](../implementation-plan.md). Spec: [module doc](../modules/05-customer-management.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

> Note: 5.5 Statements are built in Phase 4 alongside AR receipts & ageing — tracked in [tasks/07-finance.md](07-finance.md) (4.3).

> **Phase 3 go-live delivered (2026-09-23).** Shipped the customer spine the sales floor needs:
> - **5.1 Customer Profiles** ✅ — `customers` table + `Customer` model (`walkIn()`, `arBalance()`, `effectivePriceList()`), `CUST` sequence, seeded walk-in **Cash Customer** (cannot go on account or on hold), CRUD controller + `customers.*` routes, `Customers/{Index,Create,Edit,Show}.jsx` (Show carries the AR view: balance, credit limit, available credit, activity log, hold toggle). Browser-verified.
> - **5.7 Customer Groups** ✅ — `customer_groups` table + model, `Customers/Groups/Index.jsx` with inline create, group→price-list link drives `effectivePriceList()`.
> - **5.2/5.3 Trade Accounts & Credit** ✅ (enforcement) — on-account sales gated in `SalesPostingService` by credit limit (`CreditLimitExceededException`) and hold (`CustomerOnHoldException`); walk-in cannot buy on account; hold toggle on the profile. Tests: `CreditSaleFlowTest`.
> - **5.5 Statements** ✅ (2026-09-23) — `customers.statement` renders an account-statement PDF (on-account activity + running balance + current/30/60/90+ ageing) via the shared print pipeline; "Statement" action on the customer detail. Browser-verified.
> - **Deferred (later phases):** `customer_addresses` / `customer_contacts` tables, blacklist workflow, `payment_terms` table + EOM due-date maths, group-default inheritance on create, account-manager assignment, POS reg lookup, statement email auto-send, loyalty, communication log. Per-branch active-branch switching (POS trades on the main branch until then). (5.4 Customer Vehicles is served by the Workshop vehicle registry.)

## 5.1 Customer Profiles  ·  [spec](../modules/05-customer-management/5.1-customer-profiles.md)  ·  Phase 3.1

### Backend
- [ ] Migration(s): `customers` (customer_number CUST-0001, type individual/business/fleet/dealer/cash, credit_limit, price_list_id, on_hold, status incl. blacklisted), `customer_addresses` (billing/delivery/postal), `customer_contacts` (receives_statements/invoices/quotes flags)
- [ ] Models + relationships + factories; seeder for the generic "Cash Customer"
- [ ] Form Requests + Policies (blacklist requires reason)
- [ ] Controller + routes (`customers.*`) + SystemRoute permission seeds
### Frontend
- [ ] Customers list (`Customers/Index.jsx`): searchable DataTable with type/status filters
- [ ] New customer (`Customers/Create.jsx`): profile + addresses + contacts
- [ ] Customer profile (`Customers/Show.jsx`): balance, price list, vehicles, sales history tabs
- [ ] Sidebar nav entry in customers module nav config
### Tests
- [ ] Feature tests: customer_number auto-generated via sequence; every customer gets a price list (defaults to retail); Cash Customer cannot be deleted or put on account
- [ ] Functional pass: create a business customer with two addresses and a statements-only contact; look them up at POS

## 5.7 Customer Groups + Payment Terms  ·  [spec](../modules/05-customer-management/5.7-customer-groups.md)  ·  Phase 3.1

### Backend
- [ ] Migration(s): `customer_groups` (default price list, discount %, credit limit, payment terms), `payment_terms` (net_days/cod/prepaid/end_of_month, early-payment discount, late penalty)
- [ ] Models + factories; seeders: groups (Retail, Mechanics, Fleet, Panel Beaters) and terms (COD, Net 30, Net 60, EOM+30)
- [ ] Group defaults applied to new members (price list, credit limit, terms)
- [ ] Form Requests + Policies + Controller + routes (`customers.groups.*`) + SystemRoute permission seeds
### Frontend
- [ ] Customer groups page (`Customers/Groups/Index.jsx`): group table with default columns
- [ ] Sidebar nav entry in customers module nav config
### Tests
- [ ] Feature tests: new customer in a group inherits group defaults; due date computed correctly for net_days vs end_of_month terms
- [ ] Functional pass: add a customer to Mechanics — trade price list and Net 30 applied automatically

## 5.4 Customer Vehicles  ·  [spec](../modules/05-customer-management/5.4-customer-vehicles.md)  ·  Phase 3 (POS fitment filter)

### Backend
- [ ] Migration(s): `customer_vehicles` (registration, make/model/variant, year, engine code — full registry extended in Phase 5)
- [ ] Models + relationships + factories
- [ ] Registration lookup endpoint for POS (reg → vehicle → fitment filter via 1.6)
- [ ] Form Requests + Policies + routes + SystemRoute permission seeds
### Frontend
- [ ] Vehicles tab on Customer profile: add/edit registered vehicles
- [ ] POS vehicle selector wired to registration lookup
### Tests
- [ ] Feature tests: registration lookup returns owner + vehicle; multiple vehicles per customer
- [ ] Functional pass: register a customer's Corolla, select it at POS, see confirmed-fit parts highlighted and non-fits flagged

## 5.2 Trade Accounts  ·  [spec](../modules/05-customer-management/5.2-trade-accounts.md)  ·  Phase 3.6

### Backend
- [ ] On-account sale support: credit_limit + payment_terms enforcement on `document_type = 'invoice'` account sales; customer `balance` maintained from posted invoices/credits/payments
- [ ] On-hold flag blocks new account sales (manual hold with reason)
- [ ] Account manager (salesperson) assignment
- [ ] Form Requests + Policies (credit limit changes require manager approval)
### Frontend
- [ ] Trade account panel on Customer profile: limit, terms, balance, aged summary, hold toggle
### Tests
- [ ] Feature tests: on-hold customer blocked at POS account tender; balance updates on invoice post and credit note
- [ ] Functional pass: put a garage on hold — ACCOUNT tender refused at POS with clear message, cash still allowed

## 5.3 Credit Management  ·  [spec](../modules/05-customer-management/5.3-credit-management.md)  ·  Phase 3.6

### Backend
- [ ] `CreditCheckService` run on every on-account sale: balance + new sale ≤ limit; no invoices overdue > 60 days; not on hold — any failure requires supervisor override PIN
- [ ] Aged debt analysis query (current / 30 / 60 / 90+ from invoice due dates)
- [ ] Auto-hold when balance exceeds 110% of credit limit; 90+ day debtors need manager approval for credit sales
- [ ] Controller + routes (`customers.credit`) + SystemRoute permission seeds
### Frontend
- [ ] Credit management page (`Customers/Credit/Index.jsx`): aged debt table, over-limit list, hold/release actions
- [ ] Supervisor override PIN dialog at POS on failed check
- [ ] Sidebar nav entry in customers module nav config
### Tests
- [ ] Feature tests: each of the three checks independently triggers override requirement; auto-hold fires at 110% of limit; 90+ day customer blocked without manager approval
- [ ] Unit tests: ageing bracket allocation from due dates
- [ ] Functional pass: sell to a customer $10 under their limit (passes), then again over it — supervisor PIN demanded; verify auto-hold after posting past 110%

## Deferred (Phase 6)
- [ ] 5.6 Loyalty programme: `loyalty_tiers` + `loyalty_transactions`, earn on retail only, 12-month expiry, redemption threshold, no double-dipping with promotions  ·  [spec](../modules/05-customer-management/5.6-loyalty-programme.md)
- [ ] 5.8 Communication log: `customer_communications` (calls/emails/visits/complaints, follow-up flags)  ·  [spec](../modules/05-customer-management/5.8-communication-log.md)
- [ ] Loyalty dashboard page (`Customers/Loyalty/Index.jsx`)
