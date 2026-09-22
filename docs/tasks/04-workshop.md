# Tasks: Workshop Management

> Phase 5 of the [implementation plan](../implementation-plan.md). Spec: [module doc](../modules/04-workshop-management.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

Skip this whole phase if the client has no workshop — nothing else depends on it.

## 4.5 Vehicle Registry  ·  [spec](../modules/04-workshop-management/4.5-vehicle-registry.md)

### Backend
- [ ] Migration(s): `customer_vehicles` (extend if created in Phase 3), `vehicle_service_history`
- [ ] Models + relationships (CustomerVehicle → make/model/variant, Customer; ServiceHistory → JobCard) + factories
- [ ] VIN validation rule (exactly 17 chars when provided) + unique registration+VIN per branch
- [ ] Form Requests + Policies
- [ ] Controller + routes (`workshop.vehicles.*`) + SystemRoute permission seeds
### Frontend
- [ ] Vehicle lookup page (`workshop.vehicles.search` → `Workshop/Vehicles/Search.jsx`)
- [ ] Vehicle history page (`workshop.vehicles.show` → `Workshop/Vehicles/Show.jsx`)
- [ ] Sidebar nav entry in workshop module nav config
### Tests
- [ ] Feature tests: VIN length rejected at 16/18 chars; duplicate active plate per branch rejected; service history retained after customer soft-delete
- [ ] Functional pass: register a vehicle, look it up by plate, view empty then populated service history

## 4.2 Labour Management + 4.3 Technicians  ·  [spec 4.2](../modules/04-workshop-management/4.2-labour-management.md) · [spec 4.3](../modules/04-workshop-management/4.3-technician-management.md)

### Backend
- [ ] Migration(s): `labour_codes`, `labour_code_rates` (make/model-specific flat rates), `labour_rates`, `technicians`, `technician_time_logs`
- [ ] Models + relationships + factories; labour code seed pack (common services: oil change, brake pads, timing belt)
- [ ] Rate resolution logic: flat_rate / actual_time / fixed_price; make-specific flat-rate override lookup
- [ ] Form Requests + Policies
- [ ] Controllers + routes (`workshop.labour.*`, technician CRUD) + SystemRoute permission seeds
### Frontend
- [ ] Labour codes page (`workshop.labour.index` → `Workshop/Labour/Index.jsx`)
- [ ] Technician profiles management (skill level, specialisations, cost/hr, availability)
### Tests
- [ ] Feature tests: make-specific flat rate wins over default; labour rate picked by customer type/branch
- [ ] Functional pass: create labour code ENG-OIL-01 with a Toyota-specific flat rate and confirm the override applies

## 4.1 Job Cards + Technician Board  ·  [spec](../modules/04-workshop-management/4.1-job-cards.md)

### Backend
- [ ] Migration(s): `job_cards`, `job_card_labours`, `job_card_parts`
- [ ] Models + relationships + factories; job_number via number sequence (JC-YYYYMMDD-XXXX)
- [ ] Status machine: open → allocated → in_progress → awaiting_parts / awaiting_customer / on_hold → quality_check → completed → invoiced → closed; cancelled
- [ ] Business rules: customer + vehicle required past `open`; authorisation_required flag halts progress on scope change; only supervisor cancels after parts issued
- [ ] Promised-time alert (notify technician + advisor 30 min before promise if not in quality_check) — uses `job_card_reminder_mins` setting
- [ ] Technician time logs (clock-on/off) + KPI calcs (efficiency, utilisation)
- [ ] Form Requests + Policies
- [ ] Controllers + routes (`workshop.jobs.*`, `workshop.board`) + SystemRoute permission seeds
### Frontend
- [ ] Job cards list (`workshop.jobs.index` → `Workshop/Jobs/Index.jsx`)
- [ ] New job card (`workshop.jobs.create` → `Workshop/Jobs/Create.jsx`) — vehicle lookup, reported fault, pre-inspection, odometer in
- [ ] Job card detail (`workshop.jobs.show` → `Workshop/Jobs/Show.jsx`) — labour lines, parts lines, status actions, costing summary
- [ ] Technician board (`workshop.board` → `Workshop/Board/Index.jsx`)
- [ ] Sidebar nav entries
### Tests
- [ ] Feature tests: cannot leave `open` without customer+vehicle; non-supervisor cancel blocked when parts issued; authorisation gate on extra work
- [ ] Unit tests: status transition matrix; efficiency/utilisation KPI maths
- [ ] Functional pass: open a job, allocate, clock time, move through quality_check to completed

## 4.4 Parts Requisition  ·  [spec](../modules/04-workshop-management/4.4-parts-requisition.md)

### Backend
- [ ] Parts request → pick → issue flow on `job_card_parts` (reservation reduces `qty_available`, issue posts `JOB_CARD_OUT` via StockLedgerService)
- [ ] Unused-part return → `RETURN_IN` stock entry, job parts cost reduced
- [ ] Auto purchase requisition when part not in stock, linked to job card
- [ ] Form Requests + Policies; routes + SystemRoute permission seeds
### Frontend
- [ ] Parts request/issue UI on job card detail (scan/pick from bin location, issued_by/issued_at)
### Tests
- [ ] Feature tests: reservation then issue moves stock and records unit_cost at issue; return reverses cost
- [ ] Unit tests: StockLedgerService JOB_CARD_OUT / RETURN_IN postings
- [ ] Functional pass: issue 2 parts to a job, return 1, verify stock ledger and job cost

## 4.8 Workshop Invoicing + 4.6 Job Costing  ·  [spec 4.8](../modules/04-workshop-management/4.8-workshop-invoicing.md) · [spec 4.6](../modules/04-workshop-management/4.6-job-costing.md)

### Backend
- [ ] Convert `completed` job → tax invoice via Sales module (labour lines type `labour`, parts lines type `part`; warranty lines excluded; VAT on non-warranty lines)
- [ ] GlPostingService: invoice posts to Sales - Labour (Workshop) 4300 + parts revenue + VAT output + COGS
- [ ] Job costing summary: labour cost (technician cost rate × actual hrs) + parts cost (AVCO) vs billed; margin %
- [ ] Write `vehicle_service_history` row on invoicing
### Frontend
- [ ] Invoice action + costing panel on job card detail (cost / billed / margin per element)
### Tests
- [ ] Feature tests: warranty lines excluded from invoice total; job status → `invoiced` with sales_document_id linked
- [ ] Unit tests: costing maths matches spec example ($60.50 cost / $137.00 billed / 55.8% margin)
- [ ] Functional pass: complete a job with labour + parts + one warranty line, invoice it, check GL journal and margin

## 4.7 Warranty Claims  ·  [spec](../modules/04-workshop-management/4.7-warranty-claims.md)

### Backend
- [ ] Migration(s): `warranty_claims`
- [ ] Model + relationships (job, failed part, supplier) + factory; claim_number sequence
- [ ] Status flow: draft → submitted → acknowledged → approved/rejected → credited; credit posts supplier credit to AP
- [ ] Form Requests + Policies; controller + routes (`workshop.warranty.*`) + SystemRoute permission seeds
### Frontend
- [ ] Warranty claims page (`workshop.warranty.index` → `Workshop/Warranty/Index.jsx`)
- [ ] Sidebar nav entry
### Tests
- [ ] Feature tests: claim links warranty labour/parts lines from job; credited claim records credit_amount
- [ ] Functional pass: raise a claim from a warranty job, mark credited, verify supplier credit

## Deferred (Phase 6)
- [ ] Workshop report suite (9.7 productivity, technician efficiency, comeback rate) — tracked in [tasks/09-reports.md](09-reports.md)
