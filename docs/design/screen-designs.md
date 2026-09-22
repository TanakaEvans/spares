# Screen Designs

> Wireframe specs for the highest-value screens. Each follows an archetype from [navigation-and-layout.md](navigation-and-layout.md); anything not specified inherits the archetype defaults.

---

## 1. POS / Counter Sale (`sales.pos`) — Operational

The most-used screen in the business. Split-pane, keyboard-first, works on desktop and counter touch-screen.

```
┌──────────────────────────────────────────────────────────────────────┐
│ POS · Harare Main · Till 1 · Tanaka      Customer: [CASH ▾]  F4      │
│                                          Vehicle:  [— add reg —] F6  │
├────────────────────────────────────┬─────────────────────────────────┤
│ [🔍 Scan or search part…      ] F2 │ CART (3)                        │
│                                    │ ─────────────────────────────── │
│ RESULTS                            │ 90915-YZZD3 Oil Filter Toyota   │
│ ┌────────────────────────────────┐ │   4 × $6.50            $26.00 ✎│
│ │90915-YZZD3 Oil Filter          │ │ BX5E-11 NGK Plug                │
│ │ Toyota OEM · ✓ fits Hilux 2016 │ │   4 × $12.00           $48.00 ✎│
│ │ Stock: 14 · Bin A-1-C-04       │ │ Castrol Magnatec 5W30 1L       │
│ │ Retail $6.50   [+ Add]         │ │   1 × $8.50             $8.50 ✎│
│ └────────────────────────────────┘ │ ─────────────────────────────── │
│ │HU7028z Mann Oil Filter         │ │ Subtotal            $82.50     │
│ │ ~ likely fit · Stock: 6        │ │ Discount             −$0.00    │
│ │ Retail $5.80   [+ Add]         │ │ VAT 15%             $12.38     │
│ └────────────────────────────────┘ │ TOTAL               $94.88     │
│                                    │                                 │
│ [Recent] [Favourites] [Suspended]  │ [Suspend] [Clear]   [PAY  F9]  │
└────────────────────────────────────┴─────────────────────────────────┘
```

- Results show **fitment confidence** when a vehicle is set (✓ / ~ / ?), stock, bin, and the price *for the selected customer's price list*.
- Superseded numbers resolve automatically with an inline notice ("90915-03002 → superseded by 90915-YZZD3").
- ✎ on a cart line opens qty/price/discount editor (`NumberPad` on touch); price edits below list minimum require supervisor PIN.
- **PAY (F9)** opens the tender dialog: amount buttons, split tender rows, on-account option (with live credit-check result), change display. Confirm posts and prints.
- Suspended sales are recalled from the tab; suspended > 24h auto-clear.

---

## 2. Part Detail (`inventory.parts.show`) — Detail

```
┌──────────────────────────────────────────────────────────────────────┐
│ ← Parts   90915-YZZD3 · Oil Filter — Toyota OEM     [Active]         │
│                                        [Edit] [Print Label] [⋯]      │
├──────────────────────────────────────────────────────────────────────┤
│ [img] │ Brand: Toyota OEM   Category: Filters › Oil    UoM: each     │
│       │ Retail $6.50 · Trade $5.50 · Cost (AVCO) $3.80 · Margin 42%  │
│       │ On hand 14 · Reserved 2 · On order 24 · Bin A-1-C-04         │
├──────────────────────────────────────────────────────────────────────┤
│ [Stock by branch] [Fitments] [Cross-refs] [Suppliers] [Movement] [Sales] │
│  … active tab content (DataTable per tab, partial reload) …          │
└──────────────────────────────────────────────────────────────────────┘
```

- Summary strip always visible above the tabs — a counter person answers "have we got it, what's it cost" without clicking.
- Movement tab = the stock ledger for this part (filterable by branch/type/date).

---

## 3. GRN Receiving (`purchasing.grns.create`) — Operational

```
┌──────────────────────────────────────────────────────────────────────┐
│ Receive Goods · against PO-20260918-0031 · NGK Distributors          │
│ Delivery note #: [______]   Carrier: [______]                        │
├──────────────────────────────────────────────────────────────────────┤
│ Part            Ordered  Recv'd  Reject  Reason      Bin             │
│ BX5E-11 Plug      100    [100]   [ 0 ]   [      ]   [A-2-B-01 ▾]    │
│ BKR6E Plug         50    [ 48]   [ 2 ]   [crushed]  [A-2-B-02 ▾]    │
│ LFR5A-11 Plug      40    [  0]   —  not in this delivery —          │
├──────────────────────────────────────────────────────────────────────┤
│ Lines: 2 of 3 · Accepted 148 · Rejected 2      [Save Draft] [POST ▶]│
└──────────────────────────────────────────────────────────────────────┘
```

- Scanning a part barcode jumps focus to that line's Recv'd field (blind-receive mode hides the Ordered column — toggle per branch setting).
- POST confirms with the stock + AP impact summary; over-receipt >10% blocks until supervisor approves inline.
- Landed-cost drawer (imports): add freight/duty rows, choose allocation method, see per-unit cost impact before posting.

---

## 4. Job Card Detail (`workshop.jobs.show`) — Detail

```
┌──────────────────────────────────────────────────────────────────────┐
│ ← Jobs   JC-2026-00107 · ACD 4577 Toyota Hilux 2.8 GD-6 (2016)      │
│ Customer: Mupfumi Motors (trade)      [IN PROGRESS]  promised 15:30  │
│                     [Add Labour] [Request Parts] [Complete ▶] [⋯]   │
├──────────────────────────────────────────────────────────────────────┤
│ Fault: "grinding noise front right when braking"                     │
│ Tech: T. Moyo · odo in 148,220 km · opened 08:12                     │
├──────────────────────────────────────────────────────────────────────┤
│ [Overview] [Labour 2] [Parts 3] [Costing] [Vehicle History]          │
│                                                                      │
│ Costing tab:                Cost      Billed     Margin              │
│   Labour (2.0h flat)       $24.00    $70.00     $46.00              │
│   Parts                    $61.20    $98.00     $36.80              │
│   TOTAL                    $85.20   $168.00     $82.80 (49%)        │
└──────────────────────────────────────────────────────────────────────┘
```

- Status chip is the workflow driver — clicking it shows only the legal next transitions for the user's role.
- Scope-growth banner appears when new lines push total > authorised amount: amber bar "Customer authorisation required — [Record approval]".
- **Complete ▶** runs the QC checklist dialog, then offers "Create invoice now?".

---

## 5. Stock Take Count (`inventory.stocktakes.count`) — Operational (tablet-first)

```
┌───────────────────────────────────────────────┐
│ Take ST-2026-004 · Aisle A · 34 of 120 lines  │
│ [Scan bin barcode to jump…]                   │
├───────────────────────────────────────────────┤
│ Bin A-1-C-04                                  │
│ 90915-YZZD3 Oil Filter Toyota                 │
│ Counted: [   13   ]  ( NumberPad )            │
│              [Skip]  [Confirm ✓]              │
├───────────────────────────────────────────────┤
│ Next: HU7028z Mann Oil Filter (same bin)      │
└───────────────────────────────────────────────┘
```

- One line at a time, big touch targets, **system qty never shown** (blind count).
- Confirm advances automatically; progress bar persists; counts save line-by-line (a dropped tablet loses nothing).

---

## 6. Executive Dashboard (`reports.dashboard`) — see Module 9.1

KPI tile row (Today / MTD / AR / Stock value) → charts grid (12-month sales bars this-vs-last-year, top-10 parts, category donut, AR ageing stacked bar) → branch comparison table (multi-branch). Recharts, all data as Inertia props, refresh on focus.

---

## 7. Customer Profile (`customers.show`) — Detail

Summary strip: balance (red if overdue), credit limit + used %, hold badge, loyalty tier/points, assigned rep. Tabs: **Overview · Invoices · Payments · Vehicles · Quotes · Communications · Statement**. Header actions: New Sale (jumps to POS with customer pre-selected), Record Payment, Statement PDF.

---

## 8. Fitment Lookup (`vehicle-ref.fitment`) — List (public-facing counter tool)

`VehiclePicker` (make → model → variant/year, or registration) on the left; the right pane lists compatible parts grouped by category with stock + price — effectively "the catalogue for this car". A "Print pick list" action turns a selection into a picking slip. This same component embeds in POS behind the F6 vehicle button.
