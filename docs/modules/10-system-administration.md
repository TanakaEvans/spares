# Module 10: System Administration

> Existing module. Manages users, roles, company configuration, branches, departments, and system-wide settings. This module already has a working implementation — the documentation below describes both what exists and planned extensions.

---

## Sub-modules

| # | Sub-module | Status | Description |
|---|-----------|--------|-------------|
| 10.1 | Users & Roles | ✅ Existing | User management, RBAC |
| 10.2 | Company Setup | ✅ Existing | Company details, logo |
| 10.3 | Branch Management | ✅ Existing | Multi-branch configuration |
| 10.4 | Departments & Sections | ✅ Existing | Org structure |
| 10.5 | System Settings | 🔧 Extend | Application-wide settings |
| 10.6 | Activity Logs | ✅ Existing | Audit trail |
| 10.7 | Backup Management | ✅ Existing | DB backup scheduling |
| 10.8 | Password Policy | ✅ Existing | Password rules |
| 10.9 | Number Sequences | 🆕 New | Configure document numbering |
| 10.10 | Print Templates | 🆕 New | Invoice, PO, job card templates |
| 10.11 | Email Configuration | 🆕 New | SMTP + email templates |
| 10.12 | Currencies | 🆕 New | Currency setup and exchange rates |

---

## 10.1 Users & Roles

### Existing Implementation
- Users: CRUD via `UserController`, `app/Http/Controllers/UserController.php`
- Roles: CRUD via `RoleController`, bulk assign/remove
- Route-based permissions: `role_routes` pivot table
- Middleware: `EnsureHasRole`, `AdminMiddleware`

### RBAC Model
```
User → has many → Roles → has many → SystemRoutes (permissions)
```

### Suggested Default Roles for Motor Spares

| Role | Module Access |
|------|--------------|
| **Super Admin** | All modules, all actions |
| **Branch Manager** | All modules for own branch, view-only financials |
| **Sales Manager** | Sales, Customers, Reports (sales), Price lists |
| **Cashier / Counter Staff** | POS, Quotations, Customer lookup |
| **Purchasing Officer (Buyer)** | Purchasing, Suppliers, Inventory (read), Reorder |
| **Warehouse Staff** | Inventory, GRN receiving, Stock takes, Transfers |
| **Accounts Clerk** | Finance (AR/AP), Receipts, Payments |
| **Accountant** | Finance (full), Reports (financial) |
| **Workshop Foreman** | Workshop (full), Inventory (parts issue) |
| **Technician** | Workshop (own jobs only), Parts lookup |
| **Receptionist** | Workshop intake (create job cards), Customer lookup |
| **Reports Only** | Read-only access to reports module |

### Extending the System
The existing `system_modules` and `system_routes` tables support the module system. When building new modules:
1. Seed new `SystemModule` records for each module
2. Seed `SystemRoute` records for each route in the module
3. Assign routes to roles via the Bulk Assign interface

---

## 10.2 Company Setup

### Existing + Extensions Needed

#### Current `companies` table
Extend with these motor-spares-specific columns:

| Column | Type | Notes |
|--------|------|-------|
| vat_number | varchar(30) | VAT registration number |
| tax_number | varchar(30) | Company/income tax number |
| financial_year_start | varchar(5) | "01-01" (MM-DD) or "07-01" |
| default_currency_id | bigint FK | |
| default_vat_rate | decimal(5,2) | e.g. 15.00 |
| invoice_prefix | varchar(10) | "INV" |
| quote_prefix | varchar(10) | "QT" |
| po_prefix | varchar(10) | "PO" |
| logo_path | varchar(255) | For printing on documents |
| invoice_footer_text | text | e.g. "Thank you for your business" |
| banking_details | text | For statements/invoices |

---

## 10.3 Branch Management

### Existing + Extensions

Branches are the physical locations where stock is held and sales happen.

Extended `branches` table additions:

| Column | Type | Notes |
|--------|------|-------|
| branch_code | varchar(10) | Short code for document prefixes |
| address | text | Physical address |
| phone | varchar(20) | |
| email | varchar(255) | |
| is_workshop | boolean | Does this branch have a workshop? |
| is_warehouse | boolean | Can hold stock? |
| is_head_office | boolean | |
| default_price_list_id | bigint FK | |
| allow_negative_stock | boolean | Per-branch setting |
| gl_profit_centre_id | bigint FK | For multi-branch P&L |

---

## 10.4 Departments & Sections

### Existing Implementation
Departments and Sections are already implemented. For the spares business context:

**Suggested departments:**
- Parts Sales
- Workshop / Service
- Purchasing / Procurement
- Finance & Accounts
- Warehouse / Stores
- Management

---

## 10.5 System Settings

### Settings Categories

| Category | Key | Default | Description |
|----------|-----|---------|-------------|
| **Inventory** | `allow_negative_stock` | `false` | Global override |
| | `default_cost_method` | `avco` | `fifo` or `avco` |
| | `low_stock_alert_days` | `3` | Days of stock = alert |
| | `stock_take_freeze_movements` | `false` | Default for new takes |
| **Sales** | `quote_expiry_days` | `30` | Default quote validity |
| | `max_discount_without_approval` | `10` | % before manager PIN |
| | `allow_credit_over_limit` | `false` | |
| | `loyalty_earn_rate` | `1` | Points per $1 |
| | `loyalty_expiry_months` | `12` | |
| **Purchasing** | `po_approval_threshold` | `5000` | Value requiring 2nd approval |
| | `grn_over_receive_tolerance` | `10` | % over-receive allowed |
| | `auto_requisition_on_reorder` | `true` | |
| **Workshop** | `job_card_reminder_mins` | `30` | Mins before promised time |
| | `warranty_labour_rate` | `0` | Rate for warranty jobs |
| | `require_vehicle_on_job` | `true` | |
| **Finance** | `vat_rate_default` | `15` | % |
| | `payment_terms_default_days` | `30` | |
| | `ar_overdue_hold_days` | `60` | Auto-hold at this age |
| | `bank_rec_tolerance` | `0.01` | Tolerance for matching |
| **Notifications** | `email_on_low_stock` | `true` | |
| | `email_on_po_overdue` | `true` | |
| | `email_on_job_overdue` | `true` | |

---

## 10.9 Number Sequences

### Purpose
Configure the format for all auto-generated document numbers.

### `number_sequences`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| type | varchar(30) | `invoice`, `quote`, `po`, `grn`, `job_card`, etc. |
| branch_id | bigint FK | Null = global |
| prefix | varchar(10) | "INV" |
| include_date | boolean | Include YYYYMMDD in number |
| date_format | varchar(10) | "Ymd", "Ym", "Y" |
| suffix | varchar(10) | Optional suffix |
| next_number | int | Current counter |
| padding | int | Zero-pad width (e.g. 5 = 00001) |
| reset_frequency | varchar(20) | `never`, `yearly`, `monthly` |
| last_reset_at | date | |

**Example generated numbers:**
- `INV-20260101-00042` — Invoice, with date, 5-digit sequence
- `JC-2026-00107` — Job card, year only, 5-digit sequence
- `PO-0089` — Purchase order, no date, 4-digit

---

## 10.10 Print Templates

Document templates for invoices, purchase orders, job cards, delivery notes, statements.

### Template Engine
Use **Blade** templates (Laravel's native) for all printed documents, rendered to PDF via `barryvdh/laravel-dompdf`.

### Standard Templates Needed
| Document | Template File |
|----------|--------------|
| Tax Invoice | `resources/views/print/invoice.blade.php` |
| Credit Note | `resources/views/print/credit-note.blade.php` |
| Quotation | `resources/views/print/quotation.blade.php` |
| Purchase Order | `resources/views/print/purchase-order.blade.php` |
| Delivery Note | `resources/views/print/delivery-note.blade.php` |
| Job Card | `resources/views/print/job-card.blade.php` |
| Account Statement | `resources/views/print/statement.blade.php` |
| Goods Received Note | `resources/views/print/grn.blade.php` |
| Stock Take Sheet | `resources/views/print/stock-take.blade.php` |
| Receipt (80mm thermal) | `resources/views/print/receipt-thermal.blade.php` |

---

## 10.11 Email Configuration

### `email_templates`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| type | varchar(30) | `invoice`, `statement`, `quote`, `po`, `low_stock_alert` |
| subject | varchar(255) | Supports variables: `{customer_name}`, `{invoice_number}` |
| body | text | HTML with variables |
| cc | varchar(255) | Default CC addresses |
| is_active | boolean | |

### Transactional Emails
| Trigger | Recipient | Template |
|---------|-----------|---------|
| Invoice created | Customer | Invoice PDF attached |
| Quote created | Customer | Quote PDF attached |
| PO confirmed | Supplier | PO PDF attached |
| Statement generated | Customer | Statement PDF attached |
| Low stock alert | Buyer | Part list |
| Job card completed | Customer | Completion notification |
| Scheduled report | Configured recipients | Report attached |

---

## 10.12 Currencies

### `currencies`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| code | varchar(3) | ISO 4217: "USD", "ZAR", "ZWG" |
| name | varchar(50) | "US Dollar" |
| symbol | varchar(5) | "$" |
| decimal_places | int | Usually 2 |
| is_base | boolean | The business's home currency |
| is_active | boolean | |

### `exchange_rates`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| from_currency_id | bigint FK | |
| to_currency_id | bigint FK | |
| rate | decimal(15,6) | How many `to` per 1 `from` |
| effective_date | date | |
| source | varchar(20) | `manual`, `api` |

### Business Rules
1. The base currency is set once at company setup and cannot be changed after transactions exist.
2. Exchange rates are date-specific — historical rates are preserved for reporting accuracy.
3. FX gains/losses on purchase orders (rate at PO vs rate at payment) are posted to a dedicated FX variance GL account.

---

## Pages (Existing + New)

| Page | Status | Route |
|------|--------|-------|
| Users | ✅ | `auth.users.index` |
| Roles | ✅ | `auth.roles.index` |
| Company | ✅ | `admin.company.index` |
| Branches | ✅ | `admin.branches.index` |
| Departments | ✅ | `admin.departments.index` |
| Sections | ✅ | `admin.sections.index` |
| System Settings | 🔧 Extend | `admin.settings.index` |
| Number Sequences | 🆕 | `admin.sequences.index` |
| Print Templates | 🆕 | `admin.templates.index` |
| Email Config | 🆕 | `admin.email.index` |
| Currencies | 🆕 | `admin.currencies.index` |
| Exchange Rates | 🆕 | `admin.exchange-rates.index` |
| Activity Logs | ✅ | `admin.logs.index` |
| Backup | ✅ | `admin.backup.index` |
