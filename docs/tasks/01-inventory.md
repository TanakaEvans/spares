# Tasks: Inventory Management

> Phases 1–3 of the [implementation plan](../implementation-plan.md) (master data in Phase 1, adjustments/transfers/reorder in Phase 2, stock takes in Phase 3). Spec: [module doc](../modules/01-inventory-management.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

## 1.8 Category Management (+ brands, units of measure)  ·  [spec](../modules/01-inventory-management/1.8-category-management.md)

### Backend
- [ ] Migration(s): `part_categories` (parent_id tree, default GL account + VAT rate columns), `part_brands`, `units_of_measure`
- [ ] Models + relationships + factories; seeders for the standard category tree (Engine Parts → Filters → Oil Filters…), common brands, UoM (each, pair, set, litre, kg, metre)
- [ ] Form Requests + Policies
- [ ] Controller + routes (`inventory.categories.*`, `inventory.brands.*`) + SystemRoute permission seeds
### Frontend
- [ ] Categories page (`Categories/Index.jsx`): tree editor with drag/sort
- [ ] Brands page (`Brands/Index.jsx`): brand table with OEM flag + logo
- [ ] Sidebar nav entries in inventory module nav config
### Tests
- [ ] Feature tests: category cannot be deleted while parts reference it; tree nesting round-trips
- [ ] Functional pass: build a 3-level category branch, reorder siblings, deactivate a leaf

## 1.1 Parts Catalogue  ·  [spec](../modules/01-inventory-management/1.1-parts-catalogue.md)

### Backend
- [ ] Migration(s): `parts` (part_number unique, oem_number, barcodes, dimensions, status flags, soft delete), `part_images`
- [ ] Models + relationships + factories
- [ ] Part search service: part_number / oem_number / barcode / description lookup (POS reuses this)
- [ ] Form Requests + Policies (delete blocked when stock, open orders, or history exist — soft delete only)
- [ ] Controller + routes (`inventory.parts.*`) + SystemRoute permission seeds
### Frontend
- [ ] Parts list (`Parts/Index.jsx`): searchable, filterable DataTable
- [ ] Create part (`Parts/Create.jsx`): multi-tab form incl. image gallery upload + barcode fields
- [ ] Edit part (`Parts/Edit.jsx`): same form, populated
- [ ] Part detail (`Parts/Show.jsx`): part card + tabs: fitments, stock, history, cross-refs
- [ ] Sidebar nav entry in inventory module nav config
### Tests
- [ ] Feature tests: part_number unique; oem_number uniqueness is per-brand; discontinued parts blocked from new POs; delete refused when history exists
- [ ] Unit tests: part search service (barcode hit, OEM-number hit, fuzzy description)
- [ ] Functional pass: create a part with 2 images + EAN barcode, find it by scanning the barcode string, soft-delete a historyless part

## 1.6 Parts Fitment Guide  ·  [spec](../modules/01-inventory-management/1.6-parts-fitment-guide.md)

### Backend
- [ ] Migration(s): `part_fitments` (make/model/variant nullable cascade, year_from/year_to, engine_code, source, confirmed)
- [ ] Models + relationships + factories
- [ ] Fitment query service: "parts for vehicle" and "vehicles for part" (feeds POS filter and 8.4 lookup screen)
- [ ] Form Requests + Policies (source `tecdoc` records read-only — only `manual` editable)
- [ ] Controller + routes (`inventory.fitments.*`) + SystemRoute permission seeds
### Frontend
- [ ] Fitment tab on Part detail: add/edit fitment rows with make → model → variant cascading selects
- [ ] (Vehicle-side lookup screen tracked in [tasks/08-vehicle-reference.md](08-vehicle-reference.md) §8.4)
### Tests
- [ ] Feature tests: null model = all models of make; tecdoc rows reject edits; year-range matching inclusive
- [ ] Unit tests: fitment query service both directions
- [ ] Functional pass: record a fitment for Corolla 2010–2015, query with a 2013 variant (match) and 2017 (no match)

## 1.3 Bin Locations  ·  [spec](../modules/01-inventory-management/1.3-bin-locations.md)

### Backend
- [ ] Migration(s): `bin_locations` (aisle/row/shelf/bin, generated full_code, barcode), `part_bin_assignments` (is_primary, qty_in_bin)
- [ ] Models + relationships + factories
- [ ] Form Requests + Policies
- [ ] Controller + routes (`inventory.bins.*`) + SystemRoute permission seeds
### Frontend
- [ ] Bin locations page (`Bins/Index.jsx`): branch bin map with bulk-create by aisle/row range
- [ ] Bin assignment UI on Part detail (multiple bins, one primary)
- [ ] Sidebar nav entry in inventory module nav config
### Tests
- [ ] Feature tests: full_code generated as "A-1-C-04"; exactly one primary bin enforced per part+branch
- [ ] Functional pass: create an aisle of bins, assign a part to two bins, swap the primary

## 1.2 Stock Control — read-side (Phase 1), adjustments + transfers (Phase 2.5)  ·  [spec](../modules/01-inventory-management/1.2-stock-control.md)

### Backend
- [ ] Migration(s): `stock_adjustments` + `stock_adjustment_lines` (reason codes, approval), `stock_transfers` + `stock_transfer_lines` (`stock_ledger`/`stock_levels` built in Phase 0.5)
- [ ] Models + relationships + factories
- [ ] Adjustment posting via `StockLedgerService` (ADJUSTMENT_IN / ADJUSTMENT_OUT); approval required above configurable value threshold
- [ ] Transfer lifecycle service: TRANSFER_OUT posted on dispatch, TRANSFER_IN on receive, `qty_in_transit` maintained
- [ ] Form Requests + Policies
- [ ] Controller + routes (`inventory.stock.*`, `inventory.adjustments.*`, `inventory.transfers.*`) + SystemRoute permission seeds
### Frontend
- [ ] Stock levels (`Stock/Index.jsx`): matrix view parts × branches with on-hand/reserved/on-order/in-transit
- [ ] Stock adjustments (`Adjustments/Index.jsx`): list + create with reason codes and approval state
- [ ] Stock transfers (`Transfers/Index.jsx`): list + create + dispatch/receive actions
- [ ] Sidebar nav entries in inventory module nav config
### Tests
- [ ] Feature tests: adjustment above threshold requires approval before posting; transfer only reduces source stock at dispatch and increases destination at receive; negative stock blocked unless `allow_negative_stock`
- [ ] Unit tests: `qty_on_hand` always equals ledger sum for part+branch (cache reconciliation)
- [ ] Functional pass: post a damage write-off above the threshold (blocked until approved); run a two-branch transfer through dispatch → receive and watch both branch levels

## Excel Import Wizards (parts, cross-refs, fitments)  ·  Phase 1.7

### Backend
- [ ] Import framework: upload, column mapping, dry-run validation report, commit (shared with supplier price list import in [tasks/06-suppliers.md](06-suppliers.md))
- [ ] Parts import, cross-reference import, fitment import implementations
### Frontend
- [ ] Import wizard pages: upload → map columns → review errors → commit summary
### Tests
- [ ] Feature tests: duplicate part_number rows rejected with row-level errors; dry run writes nothing
- [ ] Functional pass: import a 100-row parts sheet with 3 bad rows; confirm 97 created and 3 reported

## 1.5 Reorder Management  ·  [spec](../modules/01-inventory-management/1.5-reorder-management.md)  ·  Phase 2.5

### Backend
- [ ] Reorder point/qty/max fields editing on `stock_levels`; reorder alert query (`qty_on_hand - qty_reserved ≤ reorder_point`, open-PO qty excluded)
- [ ] Auto-suggestion generation in `draft` for buyer review (full PR workflow deferred — see below)
- [ ] Controller + routes (`inventory.reorder`) + SystemRoute permission seeds
### Frontend
- [ ] Reorder report (`Reorder/Index.jsx`): below-reorder parts with create-PO actions
- [ ] Sidebar nav entry in inventory module nav config
### Tests
- [ ] Feature tests: reserved stock counts against availability; qty already on open PO excluded from alert
- [ ] Functional pass: sell a part below its reorder point, see it appear on the reorder report

## 1.4 Stock Takes  ·  [spec](../modules/01-inventory-management/1.4-stock-takes.md)  ·  Phase 3.8

### Backend
- [ ] Migration(s): `stock_takes` (full/cycle/spot, freeze_movements), `stock_take_lines` (system_qty frozen, counted/recount/final, variance)
- [ ] Models + relationships + factories
- [ ] Stock take service: freeze snapshot, movement blocking when frozen, variance calc, posting creates ledger adjustments via `StockLedgerService`
- [ ] Form Requests + Policies
- [ ] Controller + routes (`inventory.stocktakes.*`) + SystemRoute permission seeds
### Frontend
- [ ] Stock takes (`StockTakes/Index.jsx`): list + manage lifecycle, print count sheets
- [ ] Stock take count (`StockTakes/Count.jsx`): line-by-line count entry with bin-barcode sheet loading
- [ ] Sidebar nav entry in inventory module nav config
### Tests
- [ ] Feature tests: sales/GRN blocked for branch while `freeze_movements` take is open; variance above threshold forces recount; posting writes adjustment ledger entries; accepted variances notify management
- [ ] Unit tests: variance qty/value calculation
- [ ] Functional pass: run a cycle count over one aisle with a deliberate miscount — recount forced, approve, verify ledger and stock levels corrected

## Deferred (Phase 6)
- [ ] 1.7 Serial & batch tracking: `serial_numbers` + `batch_numbers` tables, capture at GRN, serial pick at POS  ·  [spec](../modules/01-inventory-management/1.7-serial-batch-tracking.md)
- [ ] Stock ageing analysis + dead stock identification reports (with 9.3 inventory reports)
- [ ] ABC analysis + EOQ / safety stock calculators (reorder depth)
