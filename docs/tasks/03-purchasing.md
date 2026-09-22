# Tasks: Purchasing & Procurement

> Phase 2 of the [implementation plan](../implementation-plan.md). Spec: [module doc](../modules/03-purchasing-procurement.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

> Note: 3.1 Purchase Requisitions are skipped for v1 (manual POs suffice) — see Deferred below.

## 3.2 Purchase Orders  ·  [spec](../modules/03-purchasing-procurement/3.2-purchase-orders.md)  ·  Phase 2.3

### Backend
- [ ] Migration(s): `purchase_orders` (status draft → submitted → confirmed → partial → received → invoiced → closed/cancelled, currency + locked exchange_rate, revision), `purchase_order_lines` (supplier_part_number, qty_received/outstanding)
- [ ] Models + relationships + factories
- [ ] PO service: `PO-YYYYMMDD-XXXX` via `NumberSequenceService`; amendment/revision flow; value-threshold 2-level approval; close cancels outstanding lines
- [ ] Preferred-supplier suggestion per part (from `approved_suppliers`, 6.3) with no-approved-supplier warning
- [ ] Email PO as PDF to supplier via Phase 0.8 pipeline
- [ ] Form Requests + Policies
- [ ] Controller + routes (`purchasing.orders.*`) + SystemRoute permission seeds
### Frontend
- [ ] Purchase orders list (`Purchasing/Orders/Index.jsx`): status-filtered DataTable
- [ ] Create PO (`Purchasing/Orders/Create.jsx`): supplier select, line entry with supplier part numbers + cost from active supplier price list, currency/rate display
- [ ] Sidebar nav entry in purchasing module nav config
### Tests
- [ ] Feature tests: submitted PO cannot delete lines (qty reduction = amendment); PO above threshold requires 2-level approval; exchange rate locked at PO date; discontinued parts rejected on PO lines
- [ ] Unit tests: line/total calculations incl. discount + VAT
- [ ] Functional pass: raise a ZAR import PO above the approval threshold — blocked until both approvals; email the PDF to a test inbox

## 3.3 Goods Received Notes (GRN)  ·  [spec](../modules/03-purchasing-procurement/3.3-goods-received-notes.md)  ·  Phase 2.4

### Backend
- [ ] Migration(s): `goods_received_notes` (carrier, tracking, delivery note ref, posted_at), `grn_lines` (qty received/accepted/rejected, bin putaway, batch/expiry), `landed_costs` (freight/insurance/customs, allocation method)
- [ ] Models + relationships + factories
- [ ] GRN posting service: `PURCHASE_RECEIPT` entries via `StockLedgerService`, AVCO recalculation, PO line qty_received update + PO status roll-up (partial/received)
- [ ] Landed cost allocation into unit_cost (by_value / by_weight / equal)
- [ ] Over-receipt >10% supervisor approval; rejected qty kept out of stock pending supplier return
- [ ] Opening stock load path: opening-balance GRN / `OPENING_BALANCE` adjustment
- [ ] Form Requests + Policies + Controller + routes (`purchasing.grns.*`) + SystemRoute permission seeds
### Frontend
- [ ] Receive GRN (`Purchasing/GRNs/Create.jsx`): select PO(s), count entry, rejection flags, bin putaway assignment, landed cost capture
- [ ] Sidebar nav entry in purchasing module nav config
### Tests
- [ ] Feature tests: posting is irreversible; rejected items never enter stock; over-receipt >10% blocked without supervisor; landed costs flow into stock value; multi-PO delivery on one GRN
- [ ] Unit tests: AVCO after successive receipts at different costs; landed cost allocation methods
- [ ] Functional pass: receive a PO short with 2 rejected units and freight cost — stock, unit cost, and PO outstanding all correct; post an opening-balance GRN

## 1.2 / 1.5 Stock adjustments, transfers, reorder (Phase 2.5)

Tracked in [tasks/01-inventory.md](01-inventory.md) — meaningful only now that stock exists.

## 3.4 Supplier Invoices (AP)  ·  [spec](../modules/03-purchasing-procurement/3.4-supplier-invoices.md)  ·  Phase 2.6

### Backend
- [ ] Migration(s): `supplier_invoices` (supplier_ref, grn_id, po_id, due_date from payment terms, status draft → matched → disputed → approved → posted, payment_status)
- [ ] Models + relationships + factories
- [ ] 3-way match service: PO ↔ GRN ↔ invoice within tolerance (e.g. 2% or $10, configurable); invoice qty ≤ GRN qty; invoice date ≥ GRN date; auto-approve inside tolerance, dispute path with mandatory reason
- [ ] Posting: AP liability journal via `GlPostingService`
- [ ] Form Requests + Policies + Controller + routes (`purchasing.invoices.*`) + SystemRoute permission seeds
### Frontend
- [ ] Supplier invoices (`Purchasing/Invoices/Index.jsx`): capture + match screen showing PO/GRN/invoice side-by-side with variance highlights
- [ ] Sidebar nav entry in purchasing module nav config
### Tests
- [ ] Feature tests: invoice exceeding GRN by more than tolerance blocked; qty over GRN blocked; date before GRN blocked; dispute requires written reason; posted invoice creates balanced AP journal
- [ ] Unit tests: tolerance calculation (2% vs $10 whichever larger)
- [ ] Functional pass: capture a matching invoice (auto-approves) and an overbilled one (forced to dispute)

## 3.6 Returns to Supplier  ·  [spec](../modules/03-purchasing-procurement/3.6-returns-to-supplier.md)  ·  Phase 2.7

### Backend
- [ ] Migration(s): `supplier_returns` (reason, status draft → approved → shipped → credit_received, supplier_rma), `supplier_return_lines` (condition, unit_cost)
- [ ] Models + relationships + factories
- [ ] Posting: `RETURN_OUT` stock ledger entry; only parts received from that supplier returnable; RMA number required before shipped
- [ ] Form Requests + Policies + Controller + routes (`purchasing.returns.*`) + SystemRoute permission seeds
### Frontend
- [ ] Returns page (`Purchasing/Returns/Index.jsx`): create from GRN, RMA capture, status progression
- [ ] Sidebar nav entry in purchasing module nav config
### Tests
- [ ] Feature tests: return of a part never received from that supplier rejected; cannot mark shipped without RMA; posting reduces stock via RETURN_OUT
- [ ] Functional pass: return the 2 rejected units from the GRN scenario through to shipped

## 3.5 Supplier Credit Notes  ·  [spec](../modules/03-purchasing-procurement/3.5-supplier-credit-notes.md)  ·  Phase 2.7

### Backend
- [ ] Migration(s): supplier credit notes table (credit_number, original_invoice_id, reason, subtotal/VAT/total, status draft/posted)
- [ ] Models + factories; link back to `supplier_returns.credit_note_id`
- [ ] Posting: AP balance reduction journal via `GlPostingService`
- [ ] Form Requests + Policies + Controller + routes (`purchasing.credits.*`) + SystemRoute permission seeds
### Frontend
- [ ] Credit capture screen (list + create against invoice/return)
### Tests
- [ ] Feature tests: posted credit reduces supplier AP balance; credit links its return
- [ ] Functional pass: capture the credit for the shipped return — return status flips to credit_received, AP balance drops

## Deferred (Phase 6)
- [ ] 3.1 Purchase requisitions: `purchase_requisitions` + lines, auto-generation from reorder, approval workflow, split-across-suppliers conversion  ·  [spec](../modules/03-purchasing-procurement/3.1-purchase-requisitions.md)
- [ ] 3.7 Import management: `import_shipments`, customs/BL tracking, import document chain  ·  [spec](../modules/03-purchasing-procurement/3.7-import-management.md)
- [ ] 3.8 Price comparison tool across suppliers (last price, lead time, MOQ)  ·  [spec](../modules/03-purchasing-procurement/3.8-price-comparison.md)
- [ ] Blanket/standing orders; blind receiving mode; barcode label print at receiving
