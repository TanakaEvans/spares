# Module 2: Sales & Point of Sale (POS)

> Handles all outbound sales: walk-in counter sales, quotations, sales orders, tax invoices, credit notes, price lists, and promotions. The POS screen is the most-used screen in the business.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 2.1 | [Counter Sales (POS)](#21-counter-sales-pos) | Fast walk-in sales with barcode scanning |
| 2.2 | [Quotations](#22-quotations) | Formal quotes with expiry and conversion |
| 2.3 | [Sales Orders](#23-sales-orders) | Back-orders, delivery scheduling |
| 2.4 | [Tax Invoices](#24-tax-invoices) | Invoice generation, VAT, reprint |
| 2.5 | [Credit Notes & Returns](#25-credit-notes--returns) | Customer returns and refunds |
| 2.6 | [Price Lists](#26-price-lists) | Multiple pricing tiers |
| 2.7 | [Promotions & Discounts](#27-promotions--discounts) | Time-based deals, bulk discounts |
| 2.8 | [Lay-by Management](#28-lay-by-management) | Deposit-based payment plans |
| 2.9 | [Delivery Notes](#29-delivery-notes) | Delivery documentation |

---

## Document Flow

```
Quotation ──→ Sales Order ──→ Tax Invoice (posted) ──→ Payment
                                    │
                                    └──→ Credit Note (on return)
```

All four document types share the same `sales_documents` table, differentiated by `document_type`.

---

## 2.1 Counter Sales (POS)

### Purpose
High-speed sales screen for walk-in customers. Staff use barcode scanners to add parts, select a customer (or use the generic "Cash Customer"), choose payment method, and print a receipt. The entire transaction should complete in under 90 seconds.

### Key Features
- Barcode scan → instant part lookup
- Vehicle registration lookup → filter parts to vehicle-compatible only
- Part cross-reference search (customer arrives with an OEM number)
- Multiple line items per transaction
- Quantity and price editing per line
- Line-level and transaction-level discounts
- Multiple payment methods in one transaction (split tender)
- On-account sales for trade customers (no cash collected)
- Suspend transaction and recall (customer forgot a part)
- Cash drawer integration
- Thermal receipt printing (80mm)
- A4 tax invoice printing
- Daily X-read (shift summary) and Z-read (close-of-day)
- Void transaction (within the same shift, with reason)

### POS Screen Layout
```
┌─────────────────────────────────────────────────────────────────┐
│  [Branch] [Cashier]              [Customer: CASH]  [Vehicle: -] │
├───────────────────────────────────────┬─────────────────────────┤
│  🔍 Search part / scan barcode        │  CART                   │
│                                       │  ─────────────────────  │
│  [Recent parts]  [Favourites]         │  NGK Spark Plug  x4     │
│                                       │  BX5E-11          $12.00│
│                                       │  Castrol 5W30 1L  $8.50 │
│                                       │  ─────────────────────  │
│                                       │  Subtotal:       $82.00 │
│                                       │  VAT 15%:        $12.30 │
│                                       │  TOTAL:          $94.30 │
│                                       │                         │
│                                       │  [CASH] [CARD] [ACCOUNT]│
│                                       │  [SUSPEND]  [CLEAR]     │
│                                       │  [PROCESS PAYMENT ▶▶]  │
└───────────────────────────────────────┴─────────────────────────┘
```

---

## 2.2 Quotations

### Purpose
Formal written quotations sent to customers. Quotations are non-binding and expire after a configurable number of days (default: 30). Staff can convert a quote to a sales order or invoice with one click.

### Key Features
- Quote number auto-generated (format: QT-YYYYMMDD-XXXX)
- Expiry date (configurable default: 30 days)
- Convert to sales order or directly to invoice
- Email quote as PDF to customer
- Quote revision tracking (REV1, REV2...)
- Quote comparison (show customer multiple options)
- Win/loss reason tracking

### Business Rules
1. Expired quotes cannot be converted — they must be re-quoted.
2. Prices on a quote are not locked; when converting, the system re-checks current pricing and alerts if prices have changed.
3. A quote does not reserve stock. Stock reservation happens when converting to a sales order.

---

## 2.3 Sales Orders

### Purpose
A confirmed order that reserves stock. Used for back-orders (part not in stock), scheduled deliveries, or large trade orders that are prepared before the customer arrives.

### Key Features
- Stock reservation on order confirmation
- Back-order management (lines where stock is insufficient are back-ordered)
- Partial fulfilment (ship what's available, back-order the rest)
- Expected delivery date per line
- Customer purchase order number reference
- Order picking list generation
- Order status tracking: `draft` → `confirmed` → `picking` → `packed` → `dispatched` → `complete`

### Business Rules
1. Confirming a sales order decrements `qty_reserved` in `stock_levels`.
2. Cancelling a confirmed order releases the reservation.
3. A line marked as `back_order` generates an automatic purchase requisition if the setting is enabled.

---

## 2.4 Tax Invoices

### Purpose
The legal tax document issued to customers. Invoices are the trigger point for revenue recognition and AR postings.

### Key Features
- Invoice number: INV-YYYYMMDD-XXXX (sequential, gapless)
- VAT calculation (exclusive or inclusive pricing modes)
- Multiple VAT rates per invoice (standard, zero-rated, exempt)
- Invoice posting creates GL entries (Sales, VAT output, AR/Cash)
- Reprint with "COPY" watermark
- Batch invoicing (generate from multiple fulfilled orders)
- Proforma invoice (no GL posting)
- Credit terms display on invoice (for trade customers)

### Business Rules
1. Posted invoices cannot be edited. Use a credit note to correct errors.
2. Invoice numbers must be sequential with no gaps (for VAT audit compliance).
3. The invoice date cannot be in a closed financial period.
4. VAT is always calculated on the system price, not any manually typed total.

---

## 2.5 Credit Notes & Returns

### Purpose
Reversal of a posted invoice when a customer returns goods or is overcharged. Generates a negative AR entry and increases stock on hand.

### Return Workflow
```
Customer returns part → Counter staff verifies condition → 
Create Credit Note linked to original invoice → 
Choose: Refund cash / Credit account / Exchange → Post
```

### Credit Note Types
| Type | Description |
|------|-------------|
| `goods_return` | Part physically returned, stock goes back |
| `price_correction` | Overcharge, no stock movement |
| `short_delivery` | Fewer items delivered than invoiced |
| `warranty` | Defective part replaced under warranty |

### Database Tables — Credit Notes use `document_type = 'credit_note'` in `sales_documents`

Additional field:
- `original_invoice_id` → reference to the invoice being credited
- `return_reason` → reason code
- `stock_returned` → boolean (did stock come back?)

### Business Rules
1. A credit note must reference an original invoice (no standalone credits).
2. If `stock_returned = true`, a stock ledger entry `RETURN_IN` is created.
3. The credit note cannot exceed the original invoice value.
4. Refund to cash is only permitted if the original payment method was cash and within the same trading day, or with manager approval.

---

## 2.6 Price Lists

### Purpose
Different customer types pay different prices. Price lists allow the business to maintain separate retail, trade, wholesale, and VIP pricing without manual overrides.

### Price List Types
| Type | Description | Example discount |
|------|-------------|-----------------|
| `retail` | Default walk-in price | 0% |
| `trade` | Mechanics, garages | 10–20% |
| `wholesale` | Resellers, dealers | 25–35% |
| `vip` | Key accounts | Negotiated |
| `custom` | One-off for specific customer | Custom |

### Database Tables

#### `price_lists`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | |
| type | varchar(20) | See types above |
| currency_id | bigint FK | |
| is_tax_inclusive | boolean | Prices include VAT or not |
| effective_date | date | |
| expiry_date | date | Null = no expiry |
| is_default | boolean | Used when no list assigned |
| notes | text | |

#### `price_list_items`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| price_list_id | bigint FK | |
| part_id | bigint FK | |
| price | decimal(15,2) | Fixed price (null = use discount) |
| discount_pct | decimal(5,2) | % off the retail price |
| minimum_qty | decimal(10,2) | Min qty to qualify |

### Price Resolution Order
When determining the selling price for a part on a transaction:
1. Customer-specific override price (if set)
2. Part-specific price on the customer's price list
3. Category discount on the customer's price list
4. Customer's price list global discount %
5. Default (retail) price list

### Business Rules
1. Price lists can have an expiry date — expired lists auto-fall-back to default.
2. A customer must always have a price list assigned (defaults to `retail`).
3. Staff below a threshold authority level cannot override prices below the price list minimum.
4. All price changes are audited with before/after values.

---

## 2.7 Promotions & Discounts

### Purpose
Time-limited promotional pricing to drive sales (clearance, seasonal, brand promotions from suppliers).

### Promotion Types
| Type | Example |
|------|---------|
| `percent_discount` | "20% off all Bosch products in July" |
| `fixed_discount` | "$5 off per NGK plug" |
| `buy_x_get_y` | "Buy 4 oil filters, get 1 free" |
| `free_item` | "Buy an oil filter kit, get a crush washer free" |
| `bundle_price` | "Oil + filter kit for $25 (retail $32)" |

### Database Tables

#### `promotions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | |
| description | text | For receipts/quotes |
| type | varchar(30) | See types above |
| start_date | date | |
| end_date | date | |
| status | varchar(20) | `active`, `scheduled`, `expired`, `paused` |
| applies_to | varchar(20) | `all`, `category`, `brand`, `specific_parts` |
| customer_types | json | Which customer types qualify |
| priority | int | Higher priority wins on conflict |
| is_stackable | boolean | Can combine with other promos |

#### `promotion_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| promotion_id | bigint FK | |
| part_id | bigint FK | Null = applies by category/brand |
| category_id | bigint FK | |
| brand_id | bigint FK | |
| min_qty | decimal(10,2) | Qualifying minimum |
| discount_value | decimal(15,2) | % or fixed amount |
| free_part_id | bigint FK | For BXGY |
| free_qty | decimal(10,2) | |

---

## 2.8 Lay-by Management

### Purpose
Customers pay a deposit, take the goods home after paying in full. Common for batteries, high-value parts.

### Database Tables

#### `lay_bys`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| laybyrn | varchar(20) | Lay-by reference |
| customer_id | bigint FK | |
| document_id | bigint FK | → sales_documents |
| start_date | date | |
| expected_completion | date | |
| total_amount | decimal(15,2) | Total owed |
| paid_amount | decimal(15,2) | Running paid total |
| remaining_amount | decimal(15,2) | Computed |
| minimum_deposit_pct | decimal(5,2) | e.g. 20% |
| status | varchar(20) | `active`, `completed`, `cancelled`, `defaulted` |
| notes | text | |

#### `lay_by_payments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| lay_by_id | bigint FK | |
| payment_date | date | |
| amount | decimal(15,2) | |
| payment_method_id | bigint FK | |
| receipt_number | varchar(30) | |

### Business Rules
1. A minimum deposit percentage is required before a lay-by is created (system setting, e.g. 20%).
2. Goods are not released until payment is 100% complete.
3. Cancelled lay-bys: deposit is forfeited or refunded based on store policy (configurable).
4. A lay-by overdue by 30 days (configurable) is flagged for follow-up.

---

## 2.9 Delivery Notes

Used when goods are delivered to a customer rather than collected at the counter. Linked to a sales order or invoice.

---

## Core Database Tables

### `sales_documents`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| document_number | varchar(20) | Auto-generated per type |
| document_type | varchar(20) | `quotation`, `order`, `invoice`, `credit_note`, `proforma`, `delivery_note` |
| status | varchar(20) | `draft`, `confirmed`, `picking`, `dispatched`, `complete`, `cancelled`, `voided` |
| customer_id | bigint FK | |
| branch_id | bigint FK | |
| salesperson_id | bigint FK → users | |
| document_date | date | |
| expiry_date | date | Quotes only |
| due_date | date | Invoices: payment due |
| price_list_id | bigint FK | |
| currency_id | bigint FK | |
| exchange_rate | decimal(10,6) | |
| customer_ref | varchar(100) | Customer's PO number |
| delivery_address_id | bigint FK | |
| subtotal_excl | decimal(15,2) | Before VAT |
| vat_amount | decimal(15,2) | Total VAT |
| total_incl | decimal(15,2) | Subtotal + VAT |
| discount_amount | decimal(15,2) | Total discount given |
| paid_amount | decimal(15,2) | For invoices |
| outstanding_amount | decimal(15,2) | Computed |
| parent_id | bigint FK | self | Link to originating document |
| posted | boolean | GL posted |
| posted_at | timestamp | |
| notes | text | |

### `sales_document_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| document_id | bigint FK | |
| sort_order | int | Line display order |
| line_type | varchar(20) | `part`, `labour`, `misc`, `comment` |
| part_id | bigint FK | Null for non-part lines |
| description | varchar(255) | Auto-filled from part |
| qty | decimal(10,2) | |
| unit_price | decimal(15,2) | Price before discount |
| discount_pct | decimal(5,2) | |
| discount_amount | decimal(15,2) | Calculated |
| net_price | decimal(15,2) | After discount, before VAT |
| vat_code | varchar(10) | VAT rate code |
| vat_rate | decimal(5,2) | Rate at time of sale |
| vat_amount | decimal(15,2) | |
| line_total_excl | decimal(15,2) | |
| line_total_incl | decimal(15,2) | |
| qty_delivered | decimal(10,2) | |
| bin_location_id | bigint FK | Pick from location |
| is_back_order | boolean | |
| notes | text | |

### `payments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| payment_number | varchar(20) | |
| document_id | bigint FK | → sales_documents |
| customer_id | bigint FK | |
| payment_method_id | bigint FK | `cash`, `card`, `eft`, `account` |
| amount | decimal(15,2) | |
| change_given | decimal(15,2) | For cash |
| reference | varchar(100) | Card auth, EFT ref |
| payment_date | timestamp | |
| received_by | bigint FK → users | |
| notes | text | |

### `payment_methods`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(50) | "Cash", "Visa/Mastercard", "EFT", "Account" |
| type | varchar(20) | `cash`, `card`, `bank_transfer`, `credit_account` |
| requires_reference | boolean | |
| is_active | boolean | |
| gl_account_id | bigint FK | Where payments post |

---

## Pages to Build

| Page | Route | Component |
|------|-------|-----------|
| POS screen | `sales.pos` | `Sales/POS/Index.jsx` |
| Quotations list | `sales.quotes.index` | `Sales/Quotes/Index.jsx` |
| Create quote | `sales.quotes.create` | `Sales/Quotes/Create.jsx` |
| Sales orders list | `sales.orders.index` | `Sales/Orders/Index.jsx` |
| Invoices list | `sales.invoices.index` | `Sales/Invoices/Index.jsx` |
| Invoice detail | `sales.invoices.show` | `Sales/Invoices/Show.jsx` |
| Credit notes | `sales.credits.index` | `Sales/Credits/Index.jsx` |
| Price lists | `sales.pricelists.index` | `Sales/PriceLists/Index.jsx` |
| Promotions | `sales.promotions.index` | `Sales/Promotions/Index.jsx` |
| Lay-bys | `sales.laybys.index` | `Sales/LayBys/Index.jsx` |

---

## Integration Points

| Module | Relationship |
|--------|-------------|
| **Inventory** | Availability check, stock reservation, stock OUT on invoice |
| **Customers** | Customer lookup, price list, credit limit check |
| **Finance** | Invoice → AR, Payment → Cash/Bank, VAT output |
| **Workshop** | Workshop job → invoice through sales module |
| **Vehicle Reference** | Vehicle registration → parts fitment filter |
| **Reports** | Sales data feeds all sales analytics |
