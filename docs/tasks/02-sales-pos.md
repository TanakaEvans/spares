# Tasks: Sales & POS

> Phase 3 of the [implementation plan](../implementation-plan.md) — ends with go-live for counter sales. Spec: [module doc](../modules/02-sales-pos.md).
> Legend: `[ ]` todo · `[~]` in progress · `[x]` done — a task is only `[x]` when code is written, automated tests pass, AND the feature was functionally exercised per the [testing strategy](../testing-strategy.md). Update this file in the same commit as the completed work.

## 2.6 Price Lists  ·  [spec](../modules/02-sales-pos/2.6-price-lists.md)  ·  Phase 3.2

### Backend
- [ ] Migration(s): `price_lists` (retail/trade/wholesale/vip/custom, currency, tax-inclusive flag, effective/expiry, is_default), `price_list_items` (fixed price or discount_pct, minimum_qty)
- [ ] Models + relationships + factories; seeder for the default `retail` list
- [ ] `PriceResolutionService`: customer override → part price on customer's list → category discount → list global % → default retail (the 5-step order from the spec)
- [ ] Price change audit (before/after values)
- [ ] Form Requests + Policies
- [ ] Controller + routes (`sales.pricelists.*`) + SystemRoute permission seeds
### Frontend
- [ ] Price lists page (`Sales/PriceLists/Index.jsx`): list + item editor with bulk discount entry
- [ ] Sidebar nav entry in sales module nav config
### Tests
- [ ] Feature tests: expired list falls back to default; every customer resolves to some list (retail default); price override below list minimum blocked for low-authority staff
- [ ] Unit tests: `PriceResolutionService` all 5 resolution steps + minimum_qty tiers
- [ ] Functional pass: same part priced for a retail, trade, and custom-priced customer — three different prices, all audited

## 2.1 Counter Sales (POS)  ·  [spec](../modules/02-sales-pos/2.1-counter-sales-pos.md)  ·  Phase 3.3

### Backend
- [ ] Migration(s): `sales_documents` + `sales_document_lines` (shared by all doc types), `payments`, `payment_methods` (+ seeder: Cash, Card, EFT, Account)
- [ ] Models + relationships + factories
- [ ] POS sale service: cart → invoice + split-tender payments in one transaction; suspend/recall; void within shift with reason
- [ ] X-read / Z-read shift summary endpoints
- [ ] Form Requests + Policies
- [ ] Controller + routes (`sales.pos.*`) + SystemRoute permission seeds
### Frontend
- [ ] POS screen (`Sales/POS/Index.jsx`): barcode scan → instant add, cross-ref search, vehicle-fitment filter, cart with line qty/price/discount editing, split tender, suspend/recall, CASH/CARD/ACCOUNT buttons
- [ ] Thermal receipt (80mm) + A4 tax invoice print via Phase 0.8 pipeline
- [ ] Sidebar nav entry in sales module nav config
### Tests
- [ ] Feature tests: split tender must equal total; on-account sale runs credit check; void only within same shift with reason; suspended sale recalls with intact cart
- [ ] Unit tests: cart totals (line discounts, transaction discount, VAT)
- [ ] Functional pass: scan-sell 3 parts cash+card split in under 90 seconds with receipt; sell last unit from two tills simultaneously — second till blocked; suspend a sale, recall it, complete on account

## 2.4 Tax Invoices  ·  [spec](../modules/02-sales-pos/2.4-tax-invoices.md)  ·  Phase 3.4

### Backend
- [ ] Invoice posting service: gapless `INV-YYYYMMDD-XXXX` via `NumberSequenceService`; posts stock OUT (`SALE`) via `StockLedgerService` and GL entries (Sales, VAT output, AR/Cash, COGS at AVCO) via `GlPostingService`
- [ ] VAT engine: exclusive/inclusive modes, multiple rates per invoice (standard, zero-rated, exempt), always calculated on system price
- [ ] Proforma variant (no GL posting); posted invoices immutable; closed-period date check
- [ ] Reprint with "COPY" watermark
### Frontend
- [ ] Invoices list (`Sales/Invoices/Index.jsx`) + Invoice detail (`Sales/Invoices/Show.jsx`) with print/email/credit actions
- [ ] Sidebar nav entry in sales module nav config
### Tests
- [ ] Feature tests: posted invoice rejects edits; number sequence gapless; invoice in closed period blocked; GL journal balances (revenue + VAT + COGS + stock)
- [ ] Unit tests: VAT engine (inclusive vs exclusive, mixed rates)
- [ ] Functional pass: post an invoice, verify stock ledger SALE entry + balanced journal; reprint shows COPY watermark

## 2.5 Credit Notes & Returns  ·  [spec](../modules/02-sales-pos/2.5-credit-notes-returns.md)  ·  Phase 3.5

### Backend
- [ ] Credit note as `document_type = 'credit_note'` with `original_invoice_id`, `return_reason`, `stock_returned`; types goods_return / price_correction / short_delivery / warranty
- [ ] Posting: `RETURN_IN` stock ledger entry when stock_returned; negative AR/GL reversal
- [ ] Guards: must reference an invoice; cannot exceed original invoice value; cash refund only if original was cash + same trading day, else manager approval
- [ ] Form Requests + Policies + Controller + routes (`sales.credits.*`) + SystemRoute permission seeds
### Frontend
- [ ] Credit notes page (`Sales/Credits/Index.jsx`): create-from-invoice flow with refund/credit/exchange choice
- [ ] Sidebar nav entry in sales module nav config
### Tests
- [ ] Feature tests: standalone credit rejected; over-credit rejected; price_correction makes no stock movement; goods_return writes RETURN_IN
- [ ] Functional pass: return one of four invoiced plugs next day — cash refund blocked without manager PIN, account credit succeeds, stock back on hand

## 2.2 Quotations  ·  [spec](../modules/02-sales-pos/2.2-quotations.md)  ·  Phase 3.7

### Backend
- [ ] Quotation as `document_type = 'quotation'`: `QT-YYYYMMDD-XXXX`, expiry (default 30 days, configurable), revision tracking, win/loss reason
- [ ] Convert-to-order/invoice service: expired quotes refused; prices re-checked on conversion with change alert; no stock reservation at quote stage
- [ ] Email quote as PDF via Phase 0.8 pipeline
- [ ] Form Requests + Policies + Controller + routes (`sales.quotes.*`) + SystemRoute permission seeds
### Frontend
- [ ] Quotations list (`Sales/Quotes/Index.jsx`) + Create quote (`Sales/Quotes/Create.jsx`) with convert + email actions
- [ ] Sidebar nav entry in sales module nav config
### Tests
- [ ] Feature tests: expired quote cannot convert; conversion alerts on price drift; quote reserves no stock
- [ ] Functional pass: quote a trade customer, bump the price list, convert — price-change alert shown, order carries current prices

## 2.3 Sales Orders  ·  [spec](../modules/02-sales-pos/2.3-sales-orders.md)  ·  Phase 3.7

### Backend
- [ ] Sales order as `document_type = 'order'`: status flow draft → confirmed → picking → packed → dispatched → complete
- [ ] Reservation service: confirm increments `qty_reserved`, cancel releases it; back-order lines where stock insufficient; partial fulfilment; optional auto-PR on back-order (setting)
- [ ] Picking list generation (primary bin from 1.3)
- [ ] Form Requests + Policies + Controller + routes (`sales.orders.*`) + SystemRoute permission seeds
### Frontend
- [ ] Sales orders list (`Sales/Orders/Index.jsx`): status board, pick/pack/dispatch actions, customer PO ref
- [ ] Sidebar nav entry in sales module nav config
### Tests
- [ ] Feature tests: confirm reserves stock; cancel releases reservation; insufficient stock creates back-order line; partial fulfilment invoices only shipped qty
- [ ] Unit tests: reservation math against `qty_available` constraint
- [ ] Functional pass: order 10 with 6 on hand — 6 reserved + 4 back-ordered; dispatch the 6, invoice, receive stock, complete the back-order

## Deferred (Phase 6)
- [ ] 2.7 Promotions & discounts: `promotions` + `promotion_lines`, percent/fixed/BXGY/free-item/bundle engines, stacking + priority  ·  [spec](../modules/02-sales-pos/2.7-promotions-discounts.md)
- [ ] 2.8 Lay-by management: `lay_bys` + `lay_by_payments`, minimum deposit %, release on full payment, overdue flagging  ·  [spec](../modules/02-sales-pos/2.8-layby-management.md)
- [ ] 2.9 Delivery notes (`document_type = 'delivery_note'`, linked to order/invoice)  ·  [spec](../modules/02-sales-pos/2.9-delivery-notes.md)
- [ ] Batch invoicing from multiple fulfilled orders
