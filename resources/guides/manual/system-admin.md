# Running the System

The admin area: people, the company, settings, health and backups. Mostly set up once, then checked now and then.

## Add a staff user

1. [**System Administration → Users**](route:auth.users.index) → **Add user**.
2. Enter their name, username and a starting password (they'll change it on first login).
3. Give them one or more **roles** — Cashier, Buyer, Accountant, Workshop, Inventory Clerk, Manager, Admin. The role decides what screens and actions they get.

## Roles & permissions

[**Roles & Permissions**](route:auth.roles.index): each role grants a set of routes (screens/actions). Superuser has everything. Adjust a role to widen or narrow what that group of staff can do.

## Company details & branding

[**Company**](route:admin.company.index): your trading name, address, **VAT and registration numbers**, **banking details**, and **logo**. These print on every invoice, receipt and statement — entered once, used everywhere.

## Settings (Configuration Centre)

[**Configuration Centre**](route:admin.settings.index): VAT rate, pricing rules, document footers, security policy and more — each with a plain description. Some can be set **per branch**.

Related: [**Currencies & rates**](route:admin.currencies.index), [**Number sequences**](route:admin.sequences.index), [**Branches**](route:admin.branches.index).

## System Health

[**System Health**](route:admin.health): live integrity checks — books balanced, stock matches the ledger, control accounts reconcile, period open. **All green** means the data is trustworthy. If something shows amber/red, each check has a one-click fix or a link to the problem.

## Activity log

[**Activity Log**](route:system.logs): every posted financial event — who, when, which document and which journal — all linked.

## Backups

[**Backup Management**](route:admin.backups.index): take a database backup before big changes. Keep copies off the machine.

## Loading data

See [data-upload guide](guide:data-upload) for the Excel/CSV upload of parts, stock, suppliers and customers.
