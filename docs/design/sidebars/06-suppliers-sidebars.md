# Sub-module Sidebars: Supplier Management

Sub-module sidebars for [Module 6: Supplier Management](../../modules/06-supplier-management.md), built to the [universal template](README.md).

## 6.1 Supplier Profiles

> Context: entered via Suppliers › Supplier Profiles. [Spec](../../modules/06-supplier-management/6.1-supplier-profiles.md)

```
↰ Suppliers
▍ SUPPLIER PROFILES  (factory)
──────────────────────────────
WORK
  All Suppliers ........... suppliers.index                 (list)
  New Supplier ............ suppliers.create                (plus-circle)
QUICK LINKS ⇄
  Price Lists ............. suppliers.pricelists.index      (file-spreadsheet)
  Approved Supplier List .. suppliers.approved.index        (badge-check)
  Supplier Performance .... suppliers.performance           (trending-up)
  Finance: AP Payments .... finance.payments.index          (wallet)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The 360° detail (`suppliers.show`) — POs, invoices, price lists, performance, contacts, balances — opens from the register; onboarding runs a duplicate check.
- Bank-detail changes on the profile are dual-authorised (fraud control); balances and payments read from AP (7.4).

## 6.2 Supplier Price Lists

> Context: entered via Suppliers › Price Lists. [Spec](../../modules/06-supplier-management/6.2-supplier-price-lists.md)

```
↰ Suppliers
▍ SUPPLIER PRICE LISTS  (file-spreadsheet)
──────────────────────────────
WORK
  All Price Lists ......... suppliers.pricelists.index      (list)
  Import Price List ....... suppliers.pricelists.import     (upload)
QUICK LINKS ⇄
  Supplier Profiles ....... suppliers.index                 (factory)
  Approved Supplier List .. suppliers.approved.index        (badge-check)
  Supplier Performance .... suppliers.performance           (trending-up)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Import is a wizard (upload → mapping → matching → delta review → activate); price list detail with line items, deltas vs predecessor and activation history opens within the index.
- Discontinued or price-jumped parts flag supplier-ranking reviews in the ASL (6.3); list currency follows the supplier profile (6.1).

## 6.3 Approved Supplier List

> Context: entered via Suppliers › Approved Supplier List. [Spec](../../modules/06-supplier-management/6.3-approved-supplier-list.md)

```
↰ Suppliers
▍ APPROVED SUPPLIER LIST  (badge-check)
──────────────────────────────
WORK
  ASL Browser ............. suppliers.approved.index        (list)
QUICK LINKS ⇄
  Supplier Profiles ....... suppliers.index                 (factory)
  Price Lists ............. suppliers.pricelists.index      (file-spreadsheet)
  Supplier Performance .... suppliers.performance           (trending-up)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- The browser pivots by part, by supplier, and exceptions; the per-part ASL editor (supplier set + ranking) opens within it, and a supplier's approved parts also show as a tab on `suppliers.show`.
- Re-ranking decisions lean on performance evidence (6.4); costs shown per approved supplier come from active price lists (6.2).

## 6.4 Supplier Performance

> Context: entered via Suppliers › Supplier Performance. [Spec](../../modules/06-supplier-management/6.4-supplier-performance.md)

```
↰ Suppliers
▍ SUPPLIER PERFORMANCE  (trending-up)
──────────────────────────────
INSIGHTS
  Performance Report ...... suppliers.performance           (gauge)
QUICK LINKS ⇄
  Approved Supplier List .. suppliers.approved.index        (badge-check)
  Supplier Profiles ....... suppliers.index                 (factory)
  Supplier Contacts ....... suppliers.show (Contacts tab)   (contact-round)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- This sub-module is pure reporting, so WORK is omitted: the all-supplier KPI grid (with targets and trends) is the single entry point; per-supplier KPIs render as a tab on `suppliers.show`, and KPI drill-downs (underlying PO/GRN/return/invoice lists) open within either view.
- Lead-time accuracy measures against the profile's quoted lead time (6.1); the ASL (6.3) is the main consumer of this evidence; scorecards route to the primary contact (6.5).

## 6.5 Supplier Contacts

> Context: entered via Suppliers › Supplier Contacts. [Spec](../../modules/06-supplier-management/6.5-supplier-contacts.md)

```
↰ Suppliers
▍ SUPPLIER CONTACTS  (contact-round)
──────────────────────────────
WORK
  Contacts by Supplier .... suppliers.show (Contacts tab)   (contact-round)
INSIGHTS
  Routing Exceptions ...... suppliers.index (routing filter) (alert-triangle)
QUICK LINKS ⇄
  Supplier Profiles ....... suppliers.index                 (factory)
  Supplier Performance .... suppliers.performance           (trending-up)
  Finance: AP Payments .... finance.payments.index          (wallet)
──────────────────────────────
⌂ Dashboard · ⚙ Module Settings · ⎋ Logout
```

**Notes:**
- Contacts live on the supplier profile: the roster (responsibilities, document-routing status) and add/edit form render within `suppliers.show`; Routing Exceptions filters the register to suppliers with bounced or missing routing contacts.
- Contact changes for payment-relevant roles follow the same fraud controls as bank-detail changes; remittance advices route to the Accounts contact (7.4), scorecards to the primary contact (6.4).
