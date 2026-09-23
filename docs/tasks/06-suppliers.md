# Tasks: Supplier Management

> Phase 2 of the [implementation plan](../implementation-plan.md). Spec: [module doc](../modules/06-supplier-management.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

## ✅ Phase 2 delivery status — 2026-09-23

Delivered (tests + browser pass):
- [x] 6.1/6.5 Supplier profiles + contacts — sequenced SUPP numbers, full CRUD, detail page with Orders/Invoices/Price Lists/Contacts tabs, live AP balance
- [x] 6.2 Price lists + import wizard — CSV upload → preview with column-mapping guesses → import with part matching (part_number → OEM → cross-references) → one-active-per-supplier activation; costs pre-fill PO lines
- [x] 6.3 Approved suppliers — per-part preferred supplier feeding PO cost lookup and the reorder report; inline set-supplier control on the reorder screen
- Deferred as planned (Phase 6): 6.4 supplier performance; XLSX (in addition to CSV) import

Granular checklists below remain for the deferred items.

## 6.1 Supplier Profiles  ·  [spec](../modules/06-supplier-management/6.1-supplier-profiles.md)  ·  Phase 2.1

### Backend
- [ ] Migration(s): `suppliers` (supplier_number SUPP-0001, type local/import/manufacturer/distributor/wholesaler, currency, payment terms, credit_limit, balance, lead_time_days, minimum_order_value, banking details), `supplier_addresses` (physical/postal/returns)
- [ ] Models + relationships + factories
- [ ] Form Requests + Policies
- [ ] Controller + routes (`suppliers.*`) + SystemRoute permission seeds
### Frontend
- [ ] Suppliers list (`Suppliers/Index.jsx`): searchable DataTable with type/active filters
- [ ] New supplier (`Suppliers/Create.jsx`): profile + addresses + banking
- [ ] Supplier detail (`Suppliers/Show.jsx`): profile card + tabs: contacts, price lists, POs, AP balance
- [ ] Sidebar nav entry in suppliers module nav config
### Tests
- [ ] Feature tests: supplier_number auto-generated via sequence; import supplier defaults to its foreign currency on new POs; inactive supplier blocked from new POs
- [ ] Functional pass: create a local and an import (ZAR) supplier, open each detail page, raise a draft PO from the detail screen

## 6.5 Supplier Contacts  ·  [spec](../modules/06-supplier-management/6.5-supplier-contacts.md)  ·  Phase 2.1

### Backend
- [ ] Migration(s): `supplier_contacts` (position, email/phone/mobile, is_primary, responsible_for e.g. Orders/Returns/Accounts)
- [ ] Models + relationships + factories
- [ ] Form Requests + Policies + nested routes under `suppliers.*`
### Frontend
- [ ] Contacts tab on Supplier detail: CRUD with primary flag and responsibility
### Tests
- [ ] Feature tests: exactly one primary contact per supplier; PO email defaults to the Orders-responsible contact
- [ ] Functional pass: add two contacts, mark one primary for Orders — PO email pre-fills that address

## 6.2 Supplier Price Lists + Import Wizard  ·  [spec](../modules/06-supplier-management/6.2-supplier-price-lists.md)  ·  Phase 2.2

### Backend
- [ ] Migration(s): `supplier_price_lists` (status pending/active/superseded, effective/expiry, currency), `supplier_price_list_items` (supplier_part_number, cost_price, minimum_qty, discount_available)
- [ ] Models + relationships + factories
- [ ] Import service (shared framework with Phase 1.7 wizards): upload Excel/CSV → column mapping → match supplier part numbers to our parts via cross-references → unmatched review queue
- [ ] Price change log on import: previous price, new price, % change for buyer review before activation
- [ ] Activation service: only one `active` list per supplier — activating supersedes the previous; open unconfirmed POs keep their prices
- [ ] New-part inflow: create draft parts from unmatched rows (main new-part channel)
- [ ] Form Requests + Policies + Controller + routes (`suppliers.pricelists.*`) + SystemRoute permission seeds
### Frontend
- [ ] Price lists page (`Suppliers/PriceLists/Index.jsx`): per-supplier list with status + effective dates
- [ ] Import wizard (`Suppliers/PriceLists/Import.jsx`): upload → map columns → matched/unmatched review → price-change review → activate
- [ ] Sidebar nav entry in suppliers module nav config
### Tests
- [ ] Feature tests: activating a list supersedes the previous active one; unconfirmed PO prices not retroactively changed; unmatched rows land in review, not in items
- [ ] Unit tests: part matching via cross-reference numbers; % change calculation
- [ ] Functional pass: import a 50-row catalogue with 5 unknown part numbers — 45 matched, price-change report reviewed, activate, verify new PO picks up new costs

## 6.3 Approved Supplier List  ·  [spec](../modules/06-supplier-management/6.3-approved-supplier-list.md)  ·  Phase 2.6

### Backend
- [ ] Migration(s): `approved_suppliers` (part_id, supplier_id, is_preferred, rank, part-specific lead_time_days, minimum_order_qty)
- [ ] Models + relationships + factories
- [ ] PO integration: preferred supplier suggested per part on PO creation; warning when a part has no approved supplier
- [ ] Excel export of the approved list for buyer review
- [ ] Form Requests + Policies + Controller + routes (`suppliers.approved.*`) + SystemRoute permission seeds
### Frontend
- [ ] Approved suppliers page (`Suppliers/Approved/Index.jsx`): per-part supplier ranking editor
- [ ] Approved-suppliers panel on Part detail (rank, lead time, MOQ)
- [ ] Sidebar nav entry in suppliers module nav config
### Tests
- [ ] Feature tests: one preferred supplier per part; PO line suggests the preferred supplier; unapproved-part warning raised for the buyer
- [ ] Functional pass: approve two suppliers for one part with ranks — new PO defaults to rank 1; a part with no approved supplier shows the warning

## Deferred (Phase 6)
- [ ] 6.4 Supplier performance KPIs as calculated views/reports: on-time delivery >90%, fill rate >95%, return rate <3%, lead time accuracy ≤1.1×, invoice accuracy >98% (rolling 12 months)  ·  [spec](../modules/06-supplier-management/6.4-supplier-performance.md)
- [ ] Performance report page (`Suppliers/Performance/Index.jsx`)
