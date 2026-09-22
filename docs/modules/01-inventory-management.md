# Module 1: Inventory Management

> Core module. Every other module depends on this. Manages the full lifecycle of parts from cataloguing through stock control, bin locations, stock takes, and reorder management.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 1.1 | [Parts Catalogue](#11-parts-catalogue) | Master data for all stocked parts — SKU, description, categories, brands, barcodes |
| 1.2 | [Stock Control](#12-stock-control) | Real-time stock levels per branch, adjustments, and movement history |
| 1.3 | [Bin Locations](#13-bin-locations) | Physical warehouse shelf/bin assignments for parts |
| 1.4 | [Stock Takes](#14-stock-takes) | Full counts, cycle counts, and variance resolution |
| 1.5 | [Reorder Management](#15-reorder-management) | Min/max levels, reorder points, auto purchase requests |
| 1.6 | [Parts Fitment Guide](#16-parts-fitment-guide) | Vehicle compatibility matrix (which parts fit which cars) |
| 1.7 | [Serial & Batch Tracking](#17-serial--batch-tracking) | Individual tracking for high-value items (engines, gearboxes) |
| 1.8 | [Category Management](#18-category-management) | Hierarchical parts categorisation tree |

---

## 1.1 Parts Catalogue

### Purpose
The single source of truth for every part the business stocks or has ever stocked. Links to fitment data, pricing, suppliers, and stock levels across all branches.

### Key Features
- Part number (internal) + OEM number(s) + aftermarket cross-reference numbers
- Multi-image gallery per part
- OEM vs Aftermarket classification
- Supersession management (old part → new part number)
- Barcode support: EAN-13, Code 128, QR
- Unit of measure (each, pair, set, litre, kg, metre)
- Part dimensions and weight (for freight calculations)
- Active / Discontinued / Special Order status
- Parts can belong to multiple categories via tags

### Database Tables

#### `parts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_number | varchar(50) | Internal part number, unique |
| oem_number | varchar(100) | Original manufacturer number |
| description | varchar(255) | Full description |
| short_description | varchar(100) | For labels and POS display |
| category_id | bigint FK | → part_categories |
| subcategory_id | bigint FK | → part_categories |
| brand_id | bigint FK | → part_brands |
| unit_of_measure_id | bigint FK | → units_of_measure |
| barcode_ean | varchar(20) | EAN-13 / GTIN-13 |
| barcode_code128 | varchar(50) | Code 128 for labels |
| weight_kg | decimal(8,3) | For freight |
| length_cm | decimal(8,2) | |
| width_cm | decimal(8,2) | |
| height_cm | decimal(8,2) | |
| is_oem | boolean | OEM or aftermarket |
| has_serial_tracking | boolean | Track individual serial numbers |
| has_batch_tracking | boolean | Track by batch/lot |
| is_active | boolean | Appears in searches |
| is_discontinued | boolean | No longer stocked |
| is_special_order | boolean | Not normally stocked |
| notes | text | Internal notes |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | Soft delete |

#### `part_categories`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | e.g. "Oil Filters" |
| code | varchar(20) | e.g. "FILT-OIL" |
| parent_id | bigint FK | Null = top-level |
| sort_order | int | |
| is_active | boolean | |

**Example category tree:**
```
Engine Parts
  ├── Filters
  │   ├── Oil Filters
  │   ├── Air Filters
  │   └── Fuel Filters
  ├── Gaskets & Seals
  ├── Timing Components
  └── Pistons & Rings
Brakes
  ├── Brake Pads
  ├── Brake Discs / Rotors
  ├── Brake Shoes
  └── Brake Cylinders
Suspension & Steering
  ├── Shock Absorbers
  ├── Ball Joints
  ├── Tie Rod Ends
  └── Wheel Bearings
Electrical
  ├── Batteries
  ├── Alternators
  ├── Starters
  └── Sensors
Body & Accessories
Oils & Lubricants
Tyres & Wheels
```

#### `part_brands`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | e.g. "Bosch", "NGK", "Toyota OEM" |
| code | varchar(20) | |
| country_of_origin | varchar(100) | |
| is_oem_brand | boolean | |
| logo_path | varchar(255) | |

#### `part_cross_references`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | Our part |
| reference_number | varchar(100) | Cross-ref number |
| brand_id | bigint FK | Whose number this is |
| type | varchar(20) | `oem`, `aftermarket`, `superseded_by`, `supersedes` |
| notes | text | |

#### `part_supersessions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| old_part_id | bigint FK | Superseded part |
| new_part_id | bigint FK | Replacement part |
| effective_date | date | |
| reason | varchar(255) | Why it changed |
| is_active | boolean | |

#### `part_images`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | |
| path | varchar(255) | Storage path |
| is_primary | boolean | Main image |
| sort_order | int | |

### Business Rules
1. Part number must be unique across the system.
2. If a part is superseded, searching for the old number must surface the new number with a warning.
3. Parts marked `is_discontinued` remain in the system for history; they cannot be added to new purchase orders.
4. Deleting a part is not permitted if it has stock, open orders, or transaction history. Use `deleted_at` (soft delete).
5. `oem_number` uniqueness is per-brand — different brands may share the same number string.

### Integration Points
- **Sales & POS**: Part search, pricing, availability check
- **Purchasing**: Parts to order, supplier cost prices
- **Workshop**: Parts used on job cards
- **Vehicle Reference**: Fitment guide links parts to vehicles
- **Finance**: Part cost drives COGS

---

## 1.2 Stock Control

### Purpose
Tracks how many of each part are on hand at each branch. Every movement — sale, purchase receipt, adjustment, transfer — is written to an immutable ledger. The running balance is derived from the ledger.

### Key Features
- Per-branch stock levels
- Qty on hand, qty reserved (allocated to open orders), qty on order
- Stock movement ledger (immutable audit trail)
- Stock adjustments with reason codes (damage, write-off, correction, found)
- Inter-branch stock transfers
- Low stock alerts
- Stock ageing analysis (how long has stock been sitting)
- Dead stock identification (no movement in X days)

### Database Tables

#### `stock_levels`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | |
| branch_id | bigint FK | |
| qty_on_hand | decimal(10,2) | Current physical stock |
| qty_reserved | decimal(10,2) | Allocated to open sales orders |
| qty_on_order | decimal(10,2) | On open purchase orders |
| qty_in_transit | decimal(10,2) | On approved branch transfers |
| reorder_point | decimal(10,2) | Trigger reorder at this level |
| reorder_qty | decimal(10,2) | How much to order |
| max_level | decimal(10,2) | Maximum stock level |
| last_movement_at | timestamp | |
| last_counted_at | timestamp | |

> **Constraint**: `qty_available = qty_on_hand - qty_reserved` — never allow sales that would make this negative (unless back-orders are enabled per customer).

#### `stock_ledger`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | |
| branch_id | bigint FK | |
| transaction_type | varchar(30) | See types below |
| qty | decimal(10,2) | Positive = IN, Negative = OUT |
| unit_cost | decimal(15,2) | Cost at time of movement |
| running_balance | decimal(10,2) | Balance after this entry |
| reference_type | varchar(50) | `SalesOrder`, `GRN`, `StockAdjustment`, etc. |
| reference_id | bigint | ID of the originating record |
| notes | text | |
| user_id | bigint FK | Who triggered it |
| created_at | timestamp | |

**Transaction types:**
| Code | Description |
|------|-------------|
| `PURCHASE_RECEIPT` | Stock in from GRN |
| `SALE` | Stock out from sales invoice |
| `ADJUSTMENT_IN` | Manual increase (found, correction) |
| `ADJUSTMENT_OUT` | Manual decrease (damage, write-off) |
| `TRANSFER_OUT` | Sent to another branch |
| `TRANSFER_IN` | Received from another branch |
| `OPENING_BALANCE` | Initial stock on setup |
| `RETURN_IN` | Customer return |
| `RETURN_OUT` | Returned to supplier |
| `JOB_CARD_OUT` | Used on workshop job card |

#### `stock_adjustments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| adjustment_number | varchar(20) | |
| branch_id | bigint FK | |
| reason_code | varchar(30) | `damage`, `write_off`, `correction`, `found`, `theft`, `expiry` |
| adjusted_by | bigint FK → users | |
| approved_by | bigint FK → users | Null if not required |
| notes | text | |
| status | varchar(20) | `draft`, `approved`, `posted` |
| posted_at | timestamp | |

#### `stock_adjustment_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| adjustment_id | bigint FK | |
| part_id | bigint FK | |
| bin_location_id | bigint FK | |
| qty_system | decimal(10,2) | What system showed |
| qty_actual | decimal(10,2) | What was actually there |
| variance | decimal(10,2) | Calculated: actual - system |
| unit_cost | decimal(15,2) | For value impact |

#### `stock_transfers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| transfer_number | varchar(20) | |
| from_branch_id | bigint FK | |
| to_branch_id | bigint FK | |
| status | varchar(20) | `draft` → `submitted` → `approved` → `dispatched` → `received` → `cancelled` |
| requested_by | bigint FK → users | |
| approved_by | bigint FK → users | |
| dispatched_at | timestamp | |
| received_at | timestamp | |
| notes | text | |

#### `stock_transfer_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| transfer_id | bigint FK | |
| part_id | bigint FK | |
| from_bin_id | bigint FK | |
| to_bin_id | bigint FK | |
| qty_requested | decimal(10,2) | |
| qty_dispatched | decimal(10,2) | |
| qty_received | decimal(10,2) | |
| condition_notes | text | |

### Business Rules
1. The `stock_ledger` table is append-only — no updates or deletes once posted.
2. Stock adjustments above a configurable threshold (e.g., value > $500) require management approval before posting.
3. `qty_on_hand` is always the sum of the ledger for that part+branch — the `stock_levels` table is a materialized cache updated by service events, not the source of truth.
4. Transfers reduce source branch stock only when dispatched status is set; destination branch stock increases only when received is confirmed.
5. Negative stock is not permitted by default. System must warn and block if a transaction would cause negative stock, unless the branch has `allow_negative_stock` enabled.

---

## 1.3 Bin Locations

### Purpose
Maps the physical warehouse into addressable locations (Aisle → Row → Shelf → Bin). Parts are assigned to bins; picking slips and GRN putaway lists reference bin codes.

### Database Tables

#### `bin_locations`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| branch_id | bigint FK | |
| aisle | varchar(10) | e.g. "A" |
| row | varchar(10) | e.g. "1" |
| shelf | varchar(10) | e.g. "C" |
| bin | varchar(10) | e.g. "04" |
| full_code | varchar(30) | Generated: "A-1-C-04" |
| barcode | varchar(50) | Scannable label |
| notes | varchar(255) | |
| is_active | boolean | |

#### `part_bin_assignments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | |
| branch_id | bigint FK | |
| bin_location_id | bigint FK | |
| is_primary | boolean | Default putaway location |
| qty_in_bin | decimal(10,2) | Stock in this specific bin |

### Business Rules
1. A part can have multiple bins (e.g., overflow stock), but one must be marked `is_primary`.
2. The primary bin appears on pick slips and delivery notes.
3. Bin barcodes can be scanned during stock takes to load the count sheet for that location.

---

## 1.4 Stock Takes

### Purpose
Periodic physical counting of stock to reconcile system quantities. Supports full warehouse counts, cycle counts (subset of bins), and spot checks on specific parts.

### Types
| Type | Description | Frequency |
|------|-------------|-----------|
| Full | All parts in a branch | Annually or half-yearly |
| Cycle | A selection of bins (e.g., A-aisle this week) | Weekly/monthly rotation |
| Spot | Specific parts only | Ad hoc — usually after a discrepancy is flagged |

### Stock Take Workflow
```
Create → Freeze (optionally lock movements) → Print count sheets → Count → Enter counts → Review variances → Approve → Post
```

### Database Tables

#### `stock_takes`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| take_number | varchar(20) | |
| branch_id | bigint FK | |
| type | varchar(10) | `full`, `cycle`, `spot` |
| status | varchar(20) | `draft` → `in_progress` → `counting` → `variance_review` → `completed` |
| freeze_movements | boolean | Lock stock movements during count |
| started_by | bigint FK → users | |
| started_at | timestamp | |
| completed_by | bigint FK → users | |
| completed_at | timestamp | |
| notes | text | |

#### `stock_take_lines`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| take_id | bigint FK | |
| part_id | bigint FK | |
| bin_location_id | bigint FK | |
| system_qty | decimal(10,2) | Frozen at take creation |
| counted_qty | decimal(10,2) | What was physically counted |
| recount_qty | decimal(10,2) | If recount requested |
| final_qty | decimal(10,2) | Accepted final count |
| variance_qty | decimal(10,2) | final_qty - system_qty |
| variance_value | decimal(15,2) | variance × unit_cost |
| recount_required | boolean | Flag for second counter |
| counted_by | bigint FK → users | |

### Business Rules
1. Once a stock take is "in progress", system quantity is frozen at the start of the take for comparison purposes.
2. If `freeze_movements` is true, sales and GRN postings are blocked for the branch until the take is completed.
3. Variances above a threshold (configurable) require a mandatory recount before acceptance.
4. Posting a stock take creates stock adjustment entries in the ledger.
5. All accepted variances generate a management notification for review.

---

## 1.5 Reorder Management

### Purpose
Automatically identifies when stock is running low and generates purchase requisitions or purchase order suggestions.

### Key Features
- Per-part, per-branch reorder points and quantities
- Automatic purchase requisition generation when stock hits reorder point
- Reorder report showing all parts below reorder level
- ABC analysis (classify parts by sales velocity: A=fast, B=medium, C=slow)
- Economic Order Quantity (EOQ) calculation helper
- Safety stock calculator based on average daily usage and lead time

### Business Rules
1. Reorder alert fires when: `qty_on_hand - qty_reserved ≤ reorder_point`
2. Auto-generated requisitions are created in `draft` status and require a buyer to review and convert to a PO.
3. Parts already on an open PO are excluded from reorder alerts for that quantity.
4. ABC classification is recalculated monthly from the 12-month rolling sales history.

---

## 1.6 Parts Fitment Guide

### Purpose
The fitment guide answers: "Which parts fit this vehicle?" and "What vehicles does this part fit?" This is central to a spares business — wrong parts returned waste time and damage customer trust.

### Database Tables

#### `part_fitments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | |
| vehicle_make_id | bigint FK | |
| vehicle_model_id | bigint FK | Null = all models of make |
| vehicle_variant_id | bigint FK | Null = all variants |
| year_from | smallint | Start year applicable |
| year_to | smallint | End year (null = current) |
| engine_code | varchar(30) | Specific engine |
| notes | text | Fitment notes / caveats |
| source | varchar(20) | `tecdoc`, `manual`, `supplier` |
| confirmed | boolean | Verified fitment |

### Business Rules
1. At point of sale, if the customer has a registered vehicle, the system must highlight which parts are confirmed fits and flag any non-fits.
2. Multiple fitment records per part are allowed for different makes/models/years.
3. Source `tecdoc` records are read-only; only `manual` records may be edited by staff.

---

## 1.7 Serial & Batch Tracking

### Purpose
For high-value or regulated parts (engines, gearboxes, batteries, remanufactured units), track individual items by serial number through the supply chain and to the customer.

### Database Tables

#### `serial_numbers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | |
| serial_number | varchar(100) | Unique per part |
| branch_id | bigint FK | Current location |
| status | varchar(20) | `in_stock`, `sold`, `on_order`, `returned`, `scrapped` |
| grn_line_id | bigint FK | Where it was received |
| sale_line_id | bigint FK | Where it was sold |
| customer_id | bigint FK | Who it was sold to |
| warranty_expiry | date | |

#### `batch_numbers`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| part_id | bigint FK | |
| batch_number | varchar(100) | |
| expiry_date | date | For oils, chemicals |
| received_date | date | |
| qty_received | decimal(10,2) | |
| qty_remaining | decimal(10,2) | |

---

## 1.8 Category Management

Managed via the `part_categories` table (see 1.1). The category tree is unlimited depth but practically 2-3 levels works best:

```
Level 1: Main Group     (Engine Parts, Brakes, Suspension...)
Level 2: Sub Group      (Filters, Gaskets, Brake Pads...)
Level 3: Detail Group   (Oil Filters - Spin-on, Oil Filters - Cartridge...)
```

Each category can have a default GL account for sales and cost postings, a default VAT rate, and display settings.

---

## Pages to Build

| Page | Route | Component | Description |
|------|-------|-----------|-------------|
| Parts list | `inventory.parts.index` | `Parts/Index.jsx` | Searchable, filterable table |
| Create part | `inventory.parts.create` | `Parts/Create.jsx` | Multi-tab form |
| Edit part | `inventory.parts.edit` | `Parts/Edit.jsx` | Same form, populated |
| Part detail | `inventory.parts.show` | `Parts/Show.jsx` | Part card + tabs: fitments, stock, history, cross-refs |
| Stock levels | `inventory.stock.index` | `Stock/Index.jsx` | Matrix view: parts × branches |
| Stock adjustments | `inventory.adjustments.index` | `Adjustments/Index.jsx` | List + create |
| Stock transfers | `inventory.transfers.index` | `Transfers/Index.jsx` | List + create + dispatch/receive |
| Stock takes | `inventory.stocktakes.index` | `StockTakes/Index.jsx` | List + manage |
| Stock take count | `inventory.stocktakes.count` | `StockTakes/Count.jsx` | Line-by-line count entry |
| Reorder report | `inventory.reorder` | `Reorder/Index.jsx` | Below-reorder parts with actions |
| Bin locations | `inventory.bins.index` | `Bins/Index.jsx` | Branch bin map |
| Categories | `inventory.categories.index` | `Categories/Index.jsx` | Tree editor |
| Brands | `inventory.brands.index` | `Brands/Index.jsx` | Brand table |

---

## Integration Points

| Module | Relationship |
|--------|-------------|
| **Sales & POS** | Checks availability, reserves stock, reduces on-hand at invoice |
| **Purchasing** | Increases on-hand at GRN, feeds reorder suggestions |
| **Workshop** | Reduces on-hand when parts are issued to job cards |
| **Vehicle Reference** | Fitment data links parts to vehicles |
| **Finance** | Stock value reports, COGS postings, inventory adjustments |
| **Customers** | Customer vehicle registration feeds fitment filter at POS |
