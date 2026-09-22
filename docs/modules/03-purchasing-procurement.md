# Module 3: Purchasing & Procurement

> Controls the full inbound supply chain: from a purchase requisition raised by the floor, through purchase orders sent to suppliers, to receiving goods into the warehouse, matching supplier invoices, and handling returns.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 3.1 | [Purchase Requisitions](#31-purchase-requisitions) | Internal requests to buy parts |
| 3.2 | [Purchase Orders](#32-purchase-orders) | Formal orders sent to suppliers |
| 3.3 | [Goods Received Notes (GRN)](#33-goods-received-notes-grn) | Receiving and quality checking stock |
| 3.4 | [Supplier Invoices (AP)](#34-supplier-invoices-ap) | Matching supplier invoices to GRNs |
| 3.5 | [Supplier Credit Notes](#35-supplier-credit-notes) | Credits received from suppliers |
| 3.6 | [Returns to Supplier](#36-returns-to-supplier) | Returning damaged or incorrect goods |
| 3.7 | [Import Management](#37-import-management) | Tracking international shipments |
| 3.8 | [Price Comparison](#38-price-comparison) | Comparing supplier costs for the same part |

---

## Document Flow

```
Reorder Alert / Manual Request
         │
         ▼
  Purchase Requisition (optional)
         │
         ▼
    Purchase Order ──────────────────────────────────────────────────┐
         │                                                            │
         ▼                                                            │
  Goods Received Note (GRN) ──→ Stock Ledger (PURCHASE_RECEIPT)     │
         │                                                            │
         ▼                                                            │
  Supplier Invoice ──→ AP Ledger                                     │
         │                                                            │
         ▼                                                            │
  Supplier Payment ──→ Bank / Cash                                   │
                                                                      │
  Returns to Supplier ◄──────────────────────────────────────────────┘
```

---

## 3.1 Purchase Requisitions

### Purpose
An internal request for parts to be purchased. Generated automatically by the reorder management system or manually by warehouse staff or sales staff (e.g., for a special order for a customer).

### Key Features
- Auto-generated from reorder management (below min-stock trigger)
- Manual creation by any authorised staff member
- Linked to a customer and sales order (for special orders)
- Suggested supplier per part (from approved supplier list)
- Cost estimate from last purchase price
- Approval workflow: Requester → Buyer → Manager (above threshold)
- Buyer can split one requisition across multiple suppliers
- Convert approved lines to one or many purchase orders

### Database Tables

#### `purchase_requisitions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| pr_number | varchar(20) | PR-YYYYMMDD-XXXX |
| branch_id | bigint FK | Requesting branch |
| requested_by | bigint FK → users | |
| status | varchar(20) | `draft`, `submitted`, `approved`, `converted`, `cancelled` |
| required_date | date | When parts are needed |
| priority | varchar(10) | `normal`, `urgent`, `critical` |
| notes | text | |
| approved_by | bigint FK → users | |
| approved_at | timestamp | |

#### `purchase_requisition_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| pr_id | bigint FK | |
| part_id | bigint FK | |
| qty_requested | decimal(10,2) | |
| suggested_supplier_id | bigint FK | |
| estimated_unit_cost | decimal(15,2) | From last PO |
| customer_id | bigint FK | If special order |
| sales_order_id | bigint FK | If special order |
| converted_to_po_line_id | bigint FK | After conversion |
| notes | text | |

### Business Rules
1. Requisitions auto-generated from reorder management carry `source = 'auto'` and are approved automatically if within buyer authority.
2. Special-order requisitions (linked to a customer/sales order) are flagged `priority = urgent`.
3. A line once converted to a PO line is marked `converted` and cannot be converted again.

---

## 3.2 Purchase Orders

### Purpose
The legal document sent to the supplier. Constitutes a contractual commitment to buy the listed parts at the agreed price.

### Key Features
- PO number: PO-YYYYMMDD-XXXX
- Multi-line, multi-part per PO
- Supplier's own part numbers included per line (for their picking)
- Currency and exchange rate (for import suppliers)
- Expected delivery date per line
- PO terms printed on document (configurable template)
- Email PO as PDF to supplier
- Amendment process: if the supplier cannot fulfil exactly, create a revised PO (Rev 1, Rev 2)
- Standing / blanket orders (framework agreement with regular releases)
- PO approval levels based on total value thresholds

### Database Tables

#### `purchase_orders`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| po_number | varchar(20) | |
| supplier_id | bigint FK | |
| branch_id | bigint FK | Delivery branch |
| buyer_id | bigint FK → users | |
| status | varchar(20) | `draft`, `submitted`, `confirmed`, `partial`, `received`, `invoiced`, `closed`, `cancelled` |
| order_date | date | |
| expected_date | date | Overall expected delivery |
| currency_id | bigint FK | |
| exchange_rate | decimal(10,6) | Rate at order date |
| subtotal_excl | decimal(15,2) | |
| vat_amount | decimal(15,2) | |
| total_incl | decimal(15,2) | |
| supplier_ref | varchar(100) | Supplier's order/quote reference |
| pr_id | bigint FK | Originating requisition (if any) |
| approved_by | bigint FK → users | |
| approved_at | timestamp | |
| revision | int | Default 0 |
| notes | text | |

#### `purchase_order_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| po_id | bigint FK | |
| part_id | bigint FK | |
| supplier_part_number | varchar(100) | Supplier's code for this part |
| description | varchar(255) | |
| qty_ordered | decimal(10,2) | |
| unit_cost | decimal(15,2) | Agreed cost price |
| discount_pct | decimal(5,2) | |
| line_total_excl | decimal(15,2) | |
| vat_code | varchar(10) | |
| vat_amount | decimal(15,2) | |
| expected_date | date | Line-level expected date |
| qty_received | decimal(10,2) | Updated on GRN |
| qty_outstanding | decimal(10,2) | Computed: ordered - received |
| pr_line_id | bigint FK | Originating PR line |

### Business Rules
1. A PO in `submitted` or later status cannot have lines deleted — only quantities can be reduced (creating an amendment).
2. Currency exchange rate is locked at the PO date. The difference on GRN/invoice is recorded as a foreign exchange variance.
3. POs above a configurable value threshold require 2-level approval.
4. Closing a PO cancels all outstanding (unreceived) lines and releases any reservations.

---

## 3.3 Goods Received Notes (GRN)

### Purpose
Records the physical arrival of goods from a supplier. The GRN increases stock on hand and provides the basis for matching the supplier's invoice.

### Key Features
- Linked to one or more POs (a supplier may deliver multiple POs in one delivery)
- Over-delivery and under-delivery handling
- Quality rejection — flag damaged/incorrect items on arrival
- Putaway: assign received items to bin locations
- Batch / serial number capture at receiving
- Blind receiving option (operator doesn't see ordered qty — counts independently)
- Landed cost allocation (freight, insurance, customs split across received lines by weight/value)
- Barcode print for received items

### Receiving Workflow
```
Select PO → Print receiving sheet / use screen → 
Count and inspect goods → Enter actual received qtys → 
Flag rejections → Assign bin locations → Post GRN → 
Stock levels updated → Supplier invoice can now be matched
```

### Database Tables

#### `goods_received_notes`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| grn_number | varchar(20) | GRN-YYYYMMDD-XXXX |
| po_id | bigint FK | Primary PO |
| supplier_id | bigint FK | |
| branch_id | bigint FK | |
| received_by | bigint FK → users | |
| received_date | date | |
| status | varchar(20) | `draft`, `partial`, `complete`, `posted` |
| carrier | varchar(100) | Delivery company |
| tracking_number | varchar(100) | |
| delivery_note_number | varchar(100) | Supplier's delivery note ref |
| notes | text | |
| posted_at | timestamp | When stock was updated |

#### `grn_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| grn_id | bigint FK | |
| po_line_id | bigint FK | |
| part_id | bigint FK | |
| qty_ordered | decimal(10,2) | From PO |
| qty_received | decimal(10,2) | Actually received |
| qty_accepted | decimal(10,2) | Passed QC |
| qty_rejected | decimal(10,2) | Failed QC |
| rejection_reason | varchar(255) | |
| unit_cost | decimal(15,2) | From PO (or updated if invoice differs) |
| bin_location_id | bigint FK | Putaway location |
| batch_number | varchar(100) | |
| expiry_date | date | For perishables |

#### `landed_costs`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| grn_id | bigint FK | |
| cost_type | varchar(50) | `freight`, `insurance`, `customs_duty`, `customs_clearing`, `other` |
| supplier_id | bigint FK | Who charged this cost |
| amount | decimal(15,2) | |
| allocation_method | varchar(20) | `by_value`, `by_weight`, `equal` |
| currency_id | bigint FK | |

### Business Rules
1. Posting the GRN is irreversible. Errors must be corrected via a supplier return or stock adjustment.
2. Rejected items do not enter stock. They are tracked separately pending supplier return authorisation.
3. Over-receiving beyond 10% of PO quantity requires supervisor approval before posting.
4. Landed costs are allocated to `unit_cost` of each received line and flow into stock value.

---

## 3.4 Supplier Invoices (AP)

### Purpose
Records the supplier's tax invoice, matches it against the GRN, and creates the accounts payable liability.

### 3-Way Matching
The gold standard for AP control:
```
Purchase Order ←→ GRN ←→ Supplier Invoice
  Qty/Price agreed   Qty confirmed   Billed amount verified
```
All three must agree within tolerance before auto-approval.

### Database Tables

#### `supplier_invoices`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| invoice_number | varchar(30) | Our internal reference |
| supplier_ref | varchar(100) | Supplier's invoice number |
| supplier_id | bigint FK | |
| grn_id | bigint FK | Primary GRN |
| po_id | bigint FK | Primary PO |
| invoice_date | date | Date on supplier's invoice |
| due_date | date | Calculated from payment terms |
| currency_id | bigint FK | |
| exchange_rate | decimal(10,6) | |
| subtotal_excl | decimal(15,2) | |
| vat_amount | decimal(15,2) | |
| total_incl | decimal(15,2) | |
| status | varchar(20) | `draft`, `matched`, `disputed`, `approved`, `posted` |
| payment_status | varchar(20) | `unpaid`, `partial`, `paid` |
| approved_by | bigint FK → users | |
| posted_at | timestamp | |
| dispute_reason | text | If disputed |
| notes | text | |

### Business Rules
1. A supplier invoice cannot exceed the GRN value by more than a configurable tolerance (e.g., 2% or $10, whichever is larger).
2. Quantity on invoice cannot exceed quantity on GRN.
3. Disputed invoices must have a written reason and are escalated to management.
4. Invoice date cannot be earlier than the GRN date.

---

## 3.5 Supplier Credit Notes

Credits from suppliers (for returns, price corrections, bulk rebates).

| Column | Type |
|--------|------|
| id | bigint PK |
| credit_number | varchar(30) |
| supplier_id | bigint FK |
| original_invoice_id | bigint FK |
| credit_date | date |
| reason | varchar(255) |
| subtotal | decimal(15,2) |
| vat_amount | decimal(15,2) |
| total | decimal(15,2) |
| status | varchar(20) `draft/posted` |

---

## 3.6 Returns to Supplier

### Purpose
Return damaged, incorrect, or excess stock to suppliers.

### Return Workflow
```
Identify parts to return → Create Return Request → 
Get Supplier RMA (Return Merchandise Authorisation) → 
Pack & ship → Capture Supplier Credit Note → 
Reduce AP balance
```

### Database Tables

#### `supplier_returns`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| return_number | varchar(20) | |
| supplier_id | bigint FK | |
| original_grn_id | bigint FK | |
| return_date | date | |
| reason | varchar(20) | `damaged`, `incorrect_part`, `excess_stock`, `warranty` |
| status | varchar(20) | `draft`, `approved`, `shipped`, `credit_received` |
| supplier_rma | varchar(100) | Supplier's return authorisation |
| collection_date | date | |
| credit_note_id | bigint FK | Once credit received |

#### `supplier_return_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| return_id | bigint FK | |
| part_id | bigint FK | |
| qty_returned | decimal(10,2) | |
| unit_cost | decimal(15,2) | |
| total | decimal(15,2) | |
| condition | varchar(20) | `new`, `damaged`, `used` |
| reason_notes | text | |

### Business Rules
1. Posting a supplier return creates a `RETURN_OUT` entry in the stock ledger (reduces stock).
2. Returns can only reference parts that were received from the specified supplier.
3. A return must have a supplier RMA number before it can be marked shipped.

---

## 3.7 Import Management

### Purpose
Tracks internationally-sourced stock from proforma invoice through to GRN. Handles currency, customs, and landed costs.

### Import Document Chain
```
Supplier Proforma Invoice → 
Purchase Order (foreign currency) → 
Freight Booking → 
Commercial Invoice + Packing List → 
Bill of Lading / Airway Bill → 
Customs Entry → 
Customs Clearance → 
GRN with landed costs → 
Supplier Invoice → Payment
```

### `import_shipments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| shipment_number | varchar(20) | |
| supplier_id | bigint FK | |
| origin_country | varchar(3) | ISO country code |
| transport_mode | varchar(10) | `sea`, `air`, `road`, `rail` |
| eta | date | Expected arrival |
| bl_number | varchar(100) | Bill of lading |
| customs_entry_number | varchar(100) | |
| customs_duty | decimal(15,2) | |
| freight_cost | decimal(15,2) | |
| insurance | decimal(15,2) | |
| clearing_agent_fee | decimal(15,2) | |
| status | varchar(20) | `ordered`, `shipped`, `in_transit`, `customs`, `cleared`, `delivered` |

---

## 3.8 Price Comparison

A report/tool that shows the same part across multiple suppliers with their last-quoted prices, lead times, and minimum order quantities. Used by buyers to select the best source.

---

## Pages to Build

| Page | Route | Component |
|------|-------|-----------|
| Requisitions | `purchasing.requisitions.index` | `Purchasing/Requisitions/Index.jsx` |
| Purchase orders | `purchasing.orders.index` | `Purchasing/Orders/Index.jsx` |
| Create PO | `purchasing.orders.create` | `Purchasing/Orders/Create.jsx` |
| Receive GRN | `purchasing.grns.create` | `Purchasing/GRNs/Create.jsx` |
| Supplier invoices | `purchasing.invoices.index` | `Purchasing/Invoices/Index.jsx` |
| Returns | `purchasing.returns.index` | `Purchasing/Returns/Index.jsx` |
| Imports | `purchasing.imports.index` | `Purchasing/Imports/Index.jsx` |
| Price comparison | `purchasing.price-compare` | `Purchasing/PriceCompare/Index.jsx` |

---

## Integration Points

| Module | Relationship |
|--------|-------------|
| **Inventory** | GRN increases stock on hand |
| **Suppliers** | PO references supplier master |
| **Finance** | Supplier invoice → AP ledger, payment → bank |
| **Vehicle Reference** | Part fitment validated on PO lines |
| **Reports** | Spend by supplier, on-time delivery, fill rate |
