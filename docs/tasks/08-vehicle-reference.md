# Tasks: Vehicle & Parts Reference

> Phase 1 of the [implementation plan](../implementation-plan.md). Spec: [module doc](../modules/08-vehicle-parts-reference.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

Runs alongside [tasks/01-inventory.md](01-inventory.md) — the cross-reference, supersession and fitment *tables* (`part_cross_references`, `part_supersessions`, `part_fitments`) belong to Module 1; this file owns the vehicle master data and the reference/search screens. Seeding detail: [local-data-seeding](../integrations/local-data-seeding.md).

## 8.1 Vehicle Makes  ·  [spec](../modules/08-vehicle-parts-reference/8.1-vehicle-makes.md)

### Backend
- [ ] Migration(s): `vehicle_makes`
- [ ] Model + factory; seed pack of 29 prioritised makes (Toyota → Dongfeng, with code, country, sort_order)
- [ ] Form Requests + Policies
- [ ] Controller + routes (`vehicle-ref.makes.*`) + SystemRoute permission seeds
### Frontend
- [ ] Vehicle makes page (`vehicle-ref.makes.index` → `VehicleRef/Makes/Index.jsx`) — logo upload, sort order, active toggle
- [ ] Sidebar nav entry in vehicle-ref module nav config
### Tests
- [ ] Feature tests: make CRUD; sort_order drives popular-makes-first listing
- [ ] Functional pass: seed makes, deactivate one, confirm it disappears from selection lists

## 8.2 Vehicle Models & Variants  ·  [spec](../modules/08-vehicle-parts-reference/8.2-vehicle-models-variants.md)

### Backend
- [ ] Migration(s): `vehicle_models`, `vehicle_variants`
- [ ] Models + relationships (make → models → variants) + factories
- [ ] Seed pack: high-volume models/variants (e.g. Hilux 2.4/2.8 GD-6 variants with engine code, kW, drive, year ranges)
- [ ] Form Requests + Policies
- [ ] Controllers + routes (`vehicle-ref.models.*`, `vehicle-ref.variants.*`) + SystemRoute permission seeds
### Frontend
- [ ] Vehicle models page (`vehicle-ref.models.index` → `VehicleRef/Models/Index.jsx`) — body type, segment, year_from/to, generation
- [ ] Model variants page (`vehicle-ref.variants.index` → `VehicleRef/Variants/Index.jsx`) — engine code, fuel, transmission, drive
- [ ] Sidebar nav entries
### Tests
- [ ] Feature tests: variant year range must fall within model production years; null year_to = still in production
- [ ] Functional pass: build the Toyota Hilux tree from the spec example and browse make → model → variant

## 8.5 Engine Codes + Seed Packs  ·  [spec](../modules/08-vehicle-parts-reference/8.5-engine-codes.md)

### Backend
- [ ] Migration(s): `engine_codes`
- [ ] Model + factory; seed pack from spec (Toyota 1GD-FTV/2GD-FTV/1GR-FE…, VW EA888/EA189/CAYB, Ford Duratorq/EcoBoost, Isuzu 4JJ1/4JK1)
- [ ] Link variants to engine codes (variant.engine_code lookup)
- [ ] Form Requests + Policies; controller + routes (`vehicle-ref.engines.*`) + SystemRoute permission seeds
### Frontend
- [ ] Engine codes page (`vehicle-ref.engines.index` → `VehicleRef/Engines/Index.jsx`) — filter by make, fuel, aspiration
- [ ] Sidebar nav entry
### Tests
- [ ] Feature tests: engine code unique per make; seeded codes present with capacity/fuel/aspiration
- [ ] Functional pass: search "1GD" and land on the Hilux 2.8 engine record

## 8.3 Cross-References + 8.6 Supersessions  ·  [spec 8.3](../modules/08-vehicle-parts-reference/8.3-parts-cross-reference.md) · [spec 8.6](../modules/08-vehicle-parts-reference/8.6-supersession-management.md)

Depends on 1.1 Parts Catalogue (tables `part_cross_references`, `part_supersessions` migrated in [tasks/01-inventory.md](01-inventory.md)).

### Backend
- [ ] Cross-reference search service: resolve any of `oem` / `aftermarket` / `competitor` / `ean` / `superseded_by` numbers to the stocked part
- [ ] Supersession chain resolution: follow A → B → C to the current active part; retain old records; supersession notice payload for UI
- [ ] Form Requests + Policies; controller + routes (`vehicle-ref.cross-ref`) + SystemRoute permission seeds
### Frontend
- [ ] Cross-reference search page (`vehicle-ref.cross-ref` → `VehicleRef/CrossRef/Index.jsx`) — search by any number type, manage refs on a part
- [ ] Supersession notice banner component (reused later at POS)
- [ ] Sidebar nav entry
### Tests
- [ ] Feature tests: search by OEM 90915-YZZD3 style number finds the Mann-branded stocked part; chain A→B→C returns C with notice
- [ ] Unit tests: cross-ref resolver; supersession chain walker (incl. cycle guard)
- [ ] Functional pass: capture an OEM + competitor ref on a part and find it by both; supersede a part twice and search the oldest number

## 8.4 Fitment Lookup  ·  [spec](../modules/08-vehicle-parts-reference/8.4-fitment-guide.md)

Pairs with 1.6 Fitment Guide data capture in [tasks/01-inventory.md](01-inventory.md) (`part_fitments` table).

### Backend
- [ ] Fitment query service: vehicle (make/model/variant/year) → compatible parts grouped by category, with confidence flag (✓ Confirmed / ~ Likely / ? Verify)
- [ ] Reverse lookup: part → fitted vehicles
- [ ] Registration lookup hook (searches `customer_vehicles` — full POS wiring lands in Phase 3)
- [ ] Controller + routes (`vehicle-ref.fitment`) + SystemRoute permission seeds
### Frontend
- [ ] Fitment lookup page (`vehicle-ref.fitment` → `VehicleRef/Fitment/Index.jsx`) — make/model/year selector, grouped results as in spec flow (oil filters, air filters, timing belt…)
- [ ] Sidebar nav entry
### Tests
- [ ] Feature tests: Hilux 2.8 GD-6 2020 query returns only parts with matching fitment records; confidence flag rendered per record
- [ ] Unit tests: fitment matcher (variant + year-range overlap)
- [ ] Functional pass: seed fitments for one vehicle, run the lookup both directions (vehicle → parts, part → vehicles)

## Deferred (Phase 6)
- [ ] 8.7 Technical bulletins (`technical_bulletins` table, `vehicle-ref.bulletins.index` page, severity-based fitment warnings) — [spec](../modules/08-vehicle-parts-reference/8.7-technical-bulletins.md)
- [ ] External data source integrations (TecDoc / CarQuery / NHTSA imports) beyond the local seed packs
