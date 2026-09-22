# Module 4: Workshop Management

> Manages the repair and service bay: job cards, labour tracking, technician assignments, parts used, vehicle history, job costing, and warranty claims. Converts workshop output into billable invoices through the Sales module.

---

## Sub-modules

| # | Sub-module | Description |
|---|-----------|-------------|
| 4.1 | [Job Cards](#41-job-cards) | The core workshop document — one per vehicle visit |
| 4.2 | [Labour Management](#42-labour-management) | Labour codes, flat rates, time tracking |
| 4.3 | [Technician Management](#43-technician-management) | Technician profiles, skills, efficiency |
| 4.4 | [Parts Requisition](#44-parts-requisition) | Parts issued from stock to a job |
| 4.5 | [Vehicle Registry](#45-vehicle-registry) | Customer vehicles and full service history |
| 4.6 | [Job Costing](#46-job-costing) | Labour + parts cost vs billable value |
| 4.7 | [Warranty Claims](#47-warranty-claims) | Warranty work and supplier claim-back |
| 4.8 | [Workshop Invoicing](#48-workshop-invoicing) | Convert completed job to invoice |

---

## 4.1 Job Cards

### Purpose
A job card is opened for every vehicle that enters the workshop. It records the fault reported, the diagnosis, the work performed, all parts consumed, all labour applied, and the final cost to the customer. It is the central document of the workshop.

### Job Card Lifecycle
```
Walk-in / Booking → Open Job Card → 
Pre-inspection → Diagnose → 
Allocate Technician → Work in Progress → 
Parts Issued → Quality Check → 
Customer Approval (if additional work) → 
Complete → Invoice → Paid → Close
```

### Job Card Statuses
| Status | Description |
|--------|-------------|
| `open` | Created, technician not assigned yet |
| `allocated` | Technician assigned, not started |
| `in_progress` | Work actively happening |
| `awaiting_parts` | Waiting for stock / special order |
| `awaiting_customer` | Need customer authorisation for extra work |
| `on_hold` | Customer request or internal hold |
| `quality_check` | Job complete, being inspected |
| `completed` | Work done, ready to invoice |
| `invoiced` | Invoice raised |
| `closed` | Paid and closed |
| `cancelled` | No work done |

### Database Tables

#### `job_cards`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| job_number | varchar(20) | JC-YYYYMMDD-XXXX |
| customer_id | bigint FK | |
| customer_vehicle_id | bigint FK | |
| branch_id | bigint FK | |
| technician_id | bigint FK → employees | Primary technician |
| status | varchar(30) | See statuses above |
| priority | varchar(10) | `normal`, `urgent`, `vip` |
| job_type | varchar(20) | `repair`, `service`, `diagnostic`, `warranty`, `quote_only` |
| reported_fault | text | What the customer says is wrong |
| pre_inspection_notes | text | Condition on arrival (scratches etc.) |
| diagnosis | text | What technician found |
| work_performed | text | Summary of work done |
| authorisation_required | boolean | Flag if extra work approval needed |
| authorised_by | varchar(100) | Customer name who approved extra work |
| authorised_at | timestamp | |
| opened_at | timestamp | |
| allocated_at | timestamp | |
| started_at | timestamp | |
| completed_at | timestamp | |
| promised_at | timestamp | Promise time to customer |
| invoiced_at | timestamp | |
| odometer_in | int | Odometer at arrival |
| odometer_out | int | Odometer at departure |
| vehicle_condition_notes | text | |
| internal_notes | text | Not visible on customer invoice |
| sales_document_id | bigint FK | Invoice when raised |

#### `job_card_labours`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| job_id | bigint FK | |
| labour_code_id | bigint FK | |
| description | varchar(255) | Override description if needed |
| technician_id | bigint FK → employees | Who did this specific task |
| rate_type | varchar(20) | `flat_rate`, `actual_time`, `fixed_price` |
| estimated_hrs | decimal(5,2) | Flat rate book time |
| actual_hrs | decimal(5,2) | Actual time taken |
| billable_hrs | decimal(5,2) | What we charge (usually flat rate) |
| rate_per_hr | decimal(10,2) | Labour rate |
| subtotal | decimal(15,2) | billable_hrs × rate_per_hr |
| discount_pct | decimal(5,2) | |
| line_total | decimal(15,2) | |
| is_warranty | boolean | Warranty work — not charged to customer |
| warranty_claim_id | bigint FK | If warranty, links to claim |
| started_at | timestamp | |
| completed_at | timestamp | |

#### `job_card_parts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| job_id | bigint FK | |
| part_id | bigint FK | |
| bin_location_id | bigint FK | Issued from |
| qty | decimal(10,2) | |
| unit_cost | decimal(15,2) | Cost price at issue |
| unit_price | decimal(15,2) | Selling price |
| discount_pct | decimal(5,2) | |
| line_total | decimal(15,2) | |
| is_warranty | boolean | Not charged to customer |
| warranty_claim_id | bigint FK | |
| issued_at | timestamp | When parts left the store |
| issued_by | bigint FK → users | |

### Business Rules
1. A job card must have a customer and vehicle before it can be moved past `open` status.
2. Adding parts to a job card triggers a stock reservation. Posting the job card (or invoice) triggers the stock `JOB_CARD_OUT` ledger entry.
3. If the work scope changes materially (additional cost above X%), the system must flag `authorisation_required` and halt progress until a supervisor approves.
4. Only a supervisor can mark a job card `cancelled` if parts have already been issued.
5. Promised time alerts: system notifies the technician and service advisor 30 minutes before the promised time if the job is not yet in `quality_check`.

---

## 4.2 Labour Management

### Purpose
Defines the standard operations the workshop performs, with associated flat-rate times and prices.

### Flat Rate vs Actual Time
| Method | Description | Use case |
|--------|-------------|----------|
| **Flat rate** | Charge the book time regardless of how long it took | Standard services: oil change, brake pads, timing belt |
| **Actual time** | Charge what was actually worked | Diagnostic, electrical faults |
| **Fixed price** | Fixed charge regardless of time | Pre-quoted jobs |

### Database Tables

#### `labour_codes`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| code | varchar(20) | e.g. "ENG-OIL-01" |
| description | varchar(255) | e.g. "Engine oil & filter change" |
| category | varchar(50) | Engine, Brakes, Suspension... |
| default_rate_type | varchar(20) | |
| flat_rate_hrs | decimal(5,2) | Standard time (book time) |
| default_rate_per_hr | decimal(10,2) | Default labour rate |
| is_make_specific | boolean | Different time for different makes |
| is_active | boolean | |

#### `labour_code_rates` (make-specific flat rates)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| labour_code_id | bigint FK | |
| vehicle_make_id | bigint FK | |
| vehicle_model_id | bigint FK | Null = all models |
| flat_rate_hrs | decimal(5,2) | |

#### `labour_rates`
Different rates for different customer types or branches:
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(100) | "Standard", "Trade", "Fleet" |
| rate_per_hr | decimal(10,2) | |
| customer_type | varchar(20) | Which customer type uses this |
| branch_id | bigint FK | Null = all branches |

---

## 4.3 Technician Management

### Purpose
Technician profiles with skills matrix, productivity tracking, and assignment management.

### Database Tables

#### `technicians` (extends `employees`)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| employee_id | bigint FK | → employees |
| technician_code | varchar(20) | |
| skill_level | varchar(20) | `apprentice`, `junior`, `senior`, `master` |
| specialisations | json | Array of `category` codes |
| labour_cost_per_hr | decimal(10,2) | Internal cost (for margin calc) |
| is_available | boolean | Currently accepting jobs |
| current_job_id | bigint FK | Currently assigned job |

#### `technician_time_logs`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| technician_id | bigint FK | |
| job_id | bigint FK | |
| job_labour_id | bigint FK | |
| started_at | timestamp | Clock-on |
| ended_at | timestamp | Clock-off |
| duration_mins | int | Calculated |
| notes | text | |

### Technician KPIs
- **Efficiency ratio**: `(total flat rate hrs billed) / (total actual hrs worked)` × 100%. Target > 100%.
- **Utilisation rate**: `(productive hours) / (available hours)`. Target > 85%.
- **Comeback rate**: Jobs where vehicle returned within 30 days for same fault.

---

## 4.4 Parts Requisition

### Purpose
Parts are issued from the spares counter to the workshop against a specific job card. This creates a stock movement and ensures job costing is accurate.

### Workflow
```
Technician identifies needed parts → 
Raises parts request on job card → 
Spares counter picks and issues → 
Parts scanned out against job card → 
Stock ledger entry: JOB_CARD_OUT
```

### Business Rules
1. Parts issued to a job card are reserved first (reduces `qty_available`) then converted to a full issue when picked.
2. If a required part is not in stock, a purchase requisition is automatically generated linking to the job card.
3. Unused parts returned from a job card create a `RETURN_IN` stock entry and reduce the job's parts cost.

---

## 4.5 Vehicle Registry

### Purpose
A database of every vehicle that has ever been brought to this business. Enables service history lookup, fitment accuracy, and customer communication.

### Database Tables

#### `customer_vehicles`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| customer_id | bigint FK | Null for walk-in (unknown owner) |
| registration | varchar(20) | Number plate |
| vin | varchar(17) | Vehicle Identification Number (17 chars) |
| make_id | bigint FK | |
| model_id | bigint FK | |
| variant_id | bigint FK | |
| year | smallint | Model year |
| colour | varchar(50) | |
| engine_code | varchar(30) | |
| fuel_type | varchar(20) | |
| transmission | varchar(20) | |
| purchase_date | date | When customer bought the car |
| warranty_expiry | date | Manufacturer warranty |
| notes | text | |
| status | varchar(20) | `active`, `sold`, `written_off` |

#### `vehicle_service_history`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| vehicle_id | bigint FK | |
| job_id | bigint FK | |
| service_date | date | |
| service_type | varchar(50) | `service`, `repair`, `diagnostic` |
| odometer | int | |
| work_summary | text | |
| technician_id | bigint FK | |
| total_cost | decimal(15,2) | |

### Business Rules
1. VIN must be exactly 17 characters if provided (can be blank for older vehicles).
2. Vehicle registration + VIN must be unique per branch (cannot have two active vehicles with same plate).
3. Service history is retained permanently — even if the customer or vehicle is soft-deleted.

---

## 4.6 Job Costing

### Purpose
Compare the cost of doing a job (labour cost + parts cost) against the revenue billed to the customer. Shows gross margin per job.

### Costing Summary (displayed on job card)

| Element | Cost | Billed | Margin |
|---------|------|--------|--------|
| Labour (3.5 hrs) | $42.00 | $105.00 | $63.00 |
| Parts (oil + filter) | $18.50 | $32.00 | $13.50 |
| **Total** | **$60.50** | **$137.00** | **$76.50 (55.8%)** |

- Labour cost = technician's internal hourly cost rate × actual hours
- Parts cost = stock purchase cost (FIFO/AVCO)
- Billed = what appears on the invoice

---

## 4.7 Warranty Claims

### Purpose
When warranty work is performed on a part, the cost should be recovered from the part's supplier or manufacturer.

### Database Tables

#### `warranty_claims`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| claim_number | varchar(20) | |
| job_id | bigint FK | |
| part_id | bigint FK | The failed part |
| supplier_id | bigint FK | Who to claim from |
| claim_date | date | |
| failure_description | text | |
| failure_mileage | int | Odometer at failure |
| failure_date | date | |
| labour_hrs_claimed | decimal(5,2) | |
| parts_cost_claimed | decimal(15,2) | |
| total_claimed | decimal(15,2) | |
| status | varchar(20) | `draft`, `submitted`, `acknowledged`, `approved`, `rejected`, `credited` |
| supplier_claim_ref | varchar(100) | Supplier's reference |
| credit_amount | decimal(15,2) | What supplier approved |
| resolution_notes | text | |

---

## 4.8 Workshop Invoicing

When a job card is marked `completed`, it is converted to a tax invoice through the Sales module:
- All labour lines → invoice lines of type `labour`
- All parts lines → invoice lines of type `part`
- Warranty lines are excluded (not charged to customer)
- VAT is calculated on non-warranty lines
- Invoice is sent to customer

---

## Pages to Build

| Page | Route | Component |
|------|-------|-----------|
| Job cards list | `workshop.jobs.index` | `Workshop/Jobs/Index.jsx` |
| New job card | `workshop.jobs.create` | `Workshop/Jobs/Create.jsx` |
| Job card detail | `workshop.jobs.show` | `Workshop/Jobs/Show.jsx` |
| Vehicle lookup | `workshop.vehicles.search` | `Workshop/Vehicles/Search.jsx` |
| Vehicle history | `workshop.vehicles.show` | `Workshop/Vehicles/Show.jsx` |
| Technician board | `workshop.board` | `Workshop/Board/Index.jsx` |
| Labour codes | `workshop.labour.index` | `Workshop/Labour/Index.jsx` |
| Warranty claims | `workshop.warranty.index` | `Workshop/Warranty/Index.jsx` |

---

## Integration Points

| Module | Relationship |
|--------|-------------|
| **Inventory** | Parts issued from stock to jobs |
| **Sales** | Completed job → tax invoice |
| **Customers** | Customer vehicle registration, job history |
| **Purchasing** | Special order parts for jobs |
| **Finance** | Job invoice → AR, warranty credit → AP |
| **Suppliers** | Warranty claim-back |
