# Module 6: Supplier Management

> Manages supplier master data, price lists, performance metrics, and the approved supplier catalogue.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 6.1 | [Supplier Profiles](#61-supplier-profiles) | Master record for each supplier |
| 6.2 | [Supplier Price Lists](#62-supplier-price-lists) | Cost prices imported from supplier catalogues |
| 6.3 | [Approved Supplier List](#63-approved-supplier-list) | Preferred supplier per part |
| 6.4 | [Supplier Performance](#64-supplier-performance) | Delivery, fill rate, quality KPIs |
| 6.5 | [Supplier Contacts](#65-supplier-contacts) | Multi-contact management |

---

## 6.1 Supplier Profiles

### Supplier Types
| Type | Description |
|------|-------------|
| `local` | Domestic supplier, local currency |
| `import` | International supplier, foreign currency |
| `manufacturer` | Direct from manufacturer / OEM |
| `distributor` | Authorised distributor |
| `wholesaler` | Wholesale buying group |

### Database Tables

#### `suppliers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| supplier_number | varchar(20) | SUPP-0001 |
| name | varchar(255) | |
| trading_name | varchar(255) | |
| type | varchar(20) | See types above |
| tax_number | varchar(30) | |
| vat_number | varchar(30) | |
| currency_id | bigint FK | Default transaction currency |
| payment_terms_id | bigint FK | |
| credit_limit | decimal(15,2) | Our credit with them |
| balance | decimal(15,2) | Current AP balance |
| lead_time_days | int | Average lead time |
| minimum_order_value | decimal(15,2) | MOQ in value |
| bank_name | varchar(100) | |
| bank_branch_code | varchar(20) | |
| bank_account_number | varchar(30) | |
| bank_account_name | varchar(100) | |
| is_active | boolean | |
| notes | text | |

#### `supplier_addresses`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| supplier_id | bigint FK | |
| type | varchar(20) | `physical`, `postal`, `returns` |
| line1 | varchar(255) | |
| city | varchar(100) | |
| country | varchar(3) | ISO |
| is_default | boolean | |

---

## 6.2 Supplier Price Lists

### Purpose
Import and maintain cost prices from supplier catalogues. These drive the cost price on purchase orders and stock valuations.

### Import Process
```
Supplier sends Excel/CSV catalogue → 
Upload in system → 
Column mapping (their columns → our fields) → 
Match their part numbers to our parts (via cross-reference) → 
Review unmatched parts → 
Activate price list (supersedes previous)
```

### Database Tables

#### `supplier_price_lists`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| supplier_id | bigint FK | |
| name | varchar(100) | e.g. "NGK Jan 2026 Pricelist" |
| import_date | date | When we imported it |
| effective_date | date | When prices take effect |
| expiry_date | date | |
| currency_id | bigint FK | |
| status | varchar(20) | `pending`, `active`, `superseded` |
| notes | text | |

#### `supplier_price_list_items`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| price_list_id | bigint FK | |
| part_id | bigint FK | Our internal part |
| supplier_part_number | varchar(100) | Their code |
| cost_price | decimal(15,2) | |
| minimum_qty | decimal(10,2) | MOQ for this price |
| discount_available | decimal(5,2) | Additional % if ordering above threshold |

### Business Rules
1. Only one supplier price list can be `active` per supplier at a time. Activating a new one supersedes the previous.
2. Prices on open (unconfirmed) purchase orders are not retroactively updated when a price list changes.
3. Price list imports log all price changes: previous price, new price, % change — for buyer review before activation.

---

## 6.3 Approved Supplier List

### Purpose
For each part, define which suppliers are approved to supply it, and which one is preferred.

### `approved_suppliers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | |
| supplier_id | bigint FK | |
| is_preferred | boolean | First choice |
| rank | int | 1=preferred, 2=secondary... |
| lead_time_days | int | Supplier-specific lead time for this part |
| minimum_order_qty | decimal(10,2) | |
| notes | text | |

### Business Rules
1. When raising a purchase order, the system suggests the preferred supplier for each part.
2. Parts with no approved supplier flag a warning for the buyer.
3. The approved supplier list can be exported as an Excel report for buyer review.

---

## 6.4 Supplier Performance

### KPIs Tracked Per Supplier (rolling 12 months)

| KPI | Formula | Target |
|-----|---------|--------|
| On-time delivery rate | Orders delivered on/before expected date ÷ total orders | >90% |
| Fill rate | Lines fully received ÷ total lines ordered | >95% |
| Return rate | Lines returned ÷ total lines received | <3% |
| Lead time accuracy | Actual lead time ÷ quoted lead time | ≤1.1× |
| Invoice accuracy | Invoices matching GRN without dispute ÷ total invoices | >98% |

These are calculated views/reports — not stored as individual records.

---

## 6.5 Supplier Contacts

### `supplier_contacts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| supplier_id | bigint FK | |
| name | varchar(100) | |
| position | varchar(100) | e.g. "Sales Rep", "Account Manager" |
| email | varchar(255) | |
| phone | varchar(20) | |
| mobile | varchar(20) | |
| is_primary | boolean | |
| responsible_for | varchar(100) | e.g. "Orders", "Returns", "Accounts" |

---

## Pages to Build

| Page | Route | Component |
|------|-------|-----------|
| Suppliers list | `suppliers.index` | `Suppliers/Index.jsx` |
| New supplier | `suppliers.create` | `Suppliers/Create.jsx` |
| Supplier detail | `suppliers.show` | `Suppliers/Show.jsx` |
| Price lists | `suppliers.pricelists.index` | `Suppliers/PriceLists/Index.jsx` |
| Import price list | `suppliers.pricelists.import` | `Suppliers/PriceLists/Import.jsx` |
| Approved suppliers | `suppliers.approved.index` | `Suppliers/Approved/Index.jsx` |
| Performance report | `suppliers.performance` | `Suppliers/Performance/Index.jsx` |

---

## Integration Points

| Module | Relationship |
|--------|-------------|
| **Purchasing** | Supplier on POs, GRNs, invoices |
| **Inventory** | Cost price from supplier price list |
| **Finance** | AP balance, payment processing |
| **Workshop** | Warranty claim-back supplier |
| **Reports** | Supplier spend, performance analytics |
