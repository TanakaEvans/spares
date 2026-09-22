# Sub-module Sidebar Layouts

> **Rule: every sub-module has its OWN sidebar, showing only what concerns that sub-module** — its screens, its actions, its reports — plus a Quick Links section to related sub-modules. Entering a sub-module switches the sidebar from the module menu to the sub-module's contextual menu.

---

## The three sidebar levels

```
Level 1  Dashboard            → no sidebar (module cards)
Level 2  Module landing       → MODULE sidebar (lists its sub-modules — module-menus.md)
Level 3  Inside a sub-module  → SUB-MODULE sidebar (this spec: only that sub-module's world)
```

When the user navigates from the module menu into a sub-module (e.g. Inventory › Stock Takes), the sidebar **switches context**: the module's full list collapses away and the Stock Takes sidebar renders. One click back (`↰ Inventory`) restores the module sidebar.

## The universal sub-module sidebar template

Every sub-module sidebar follows this exact anatomy, top to bottom:

```
┌────────────────────────────┐
│ ↰ {Module name}            │ ← back to module landing (always first)
│ ────────────────────────── │
│ ▍{SUB-MODULE NAME}         │ ← context header: icon + name, accent bar
│ ────────────────────────── │
│ WORK                       │ ← the sub-module's own screens, in
│   {list / board screen}    │   workflow order (from its spec's
│   {create / operational}   │   Screens table) — NOTHING from any
│   {detail-adjacent views}  │   other sub-module ever appears here
│ ────────────────────────── │
│ INSIGHTS                   │ ← its own reports/enquiries only
│   {report or enquiry}      │   (omit section if none)
│ ────────────────────────── │
│ SETUP                      │ ← its own reference-data screens only
│   {codes / types / rules}  │   (omit if none; permission-gated)
│ ────────────────────────── │
│ QUICK LINKS ⇄              │ ← 3–6 related sub-modules, drawn from
│   {related sub-module}     │   the spec's "Related Sub-modules"
│   {related sub-module}     │   section — may cross modules
│ ────────────────────────── │
│ ⌂ Dashboard · ⚙ · ⎋       │ ← fixed bottom block (shared)
└────────────────────────────┘
```

### Hard rules

1. **Isolation**: WORK / INSIGHTS / SETUP contain only routes belonging to this sub-module. A Stock Takes sidebar never lists Parts Catalogue screens; a Quotations sidebar never lists Invoices. Crossing over happens ONLY via Quick Links.
2. **Quick Links are curated, not exhaustive**: 3–6 items max, chosen from the sub-module doc's *Related Sub-modules* section, ordered by how often the jump happens in real work (e.g. GRN → Supplier Invoices is daily; GRN → Import Management is occasional). Cross-module links are allowed and are marked with the target module's icon.
3. **Theme is untouched**: the exact chrome from [component-standards.md](../component-standards.md) §1a — same dark `bg-slate-900`, same active/hover states, same section-label style (Quick Links items render slightly muted with a `⇄` glyph to signal "you will leave this sub-module").
4. **Badges** carry live counts where the spec defines them (e.g. Stock Takes: "Variance review 2"; GRN: "POs awaiting receipt 5").
5. **Operational screens keep the sidebar collapsible**: POS, GRN receive, and stock-take count auto-collapse the sidebar to icons to maximise working space (hover/`[` re-expands); every other screen shows it expanded.
6. Permission-filtered like everything else; an empty section is omitted entirely, never rendered blank.

### Implementation

The module `nav.js` config gains a per-sub-module layer — content only, no styling (per [component-standards.md](../component-standards.md) §1a):

```js
// Modules/InventoryManagement/resources/js/nav.js
export default {
  module: [ /* Level-2 module menu — as in module-menus.md */ ],
  subModules: {
    'stock-takes': {
      label: 'Stock Takes', icon: 'clipboard-check',
      work: [
        { label: 'All Stock Takes', route: 'inventory.stocktakes.index', icon: 'list' },
        { label: 'New Stock Take',  route: 'inventory.stocktakes.create', icon: 'plus-circle' },
        { label: 'Count Entry',     route: 'inventory.stocktakes.count', icon: 'hash', badge: 'open_counts' },
        { label: 'Variance Review', route: 'inventory.stocktakes.variances', icon: 'scale', badge: 'pending_variances' },
      ],
      insights: [
        { label: 'Variance History', route: 'inventory.stocktakes.history', icon: 'history' },
      ],
      setup: [],
      quickLinks: [
        { label: 'Stock Control',   route: 'inventory.stock.index',       icon: 'layers' },
        { label: 'Bin Locations',   route: 'inventory.bins.index',        icon: 'map-pin' },
        { label: 'Adjustments',     route: 'inventory.adjustments.index', icon: 'sliders-horizontal' },
      ],
    },
    // …one entry per sub-module
  },
};
```

`ModuleLayout` resolves which sidebar to render from the current route name's prefix (`inventory.stocktakes.*` → the `stock-takes` sub-sidebar; `inventory` landing → the module menu). No page ever declares its own sidebar manually.

---

## The layouts (one file per module — all 82 sub-modules)

| Module | File |
|--------|------|
| 1 Inventory Management | [01-inventory-sidebars.md](01-inventory-sidebars.md) |
| 2 Sales & POS | [02-sales-pos-sidebars.md](02-sales-pos-sidebars.md) |
| 3 Purchasing | [03-purchasing-sidebars.md](03-purchasing-sidebars.md) |
| 4 Workshop | [04-workshop-sidebars.md](04-workshop-sidebars.md) |
| 5 Customers | [05-customers-sidebars.md](05-customers-sidebars.md) |
| 6 Suppliers | [06-suppliers-sidebars.md](06-suppliers-sidebars.md) |
| 7 Finance & Accounts | [07-finance-sidebars.md](07-finance-sidebars.md) |
| 8 Vehicle Reference | [08-vehicle-reference-sidebars.md](08-vehicle-reference-sidebars.md) |
| 9 Reports & Analytics | [09-reports-sidebars.md](09-reports-sidebars.md) |
| 10 System Administration | [10-system-admin-sidebars.md](10-system-admin-sidebars.md) |

Each file specifies, per sub-module: the full sidebar tree (WORK / INSIGHTS / SETUP / QUICK LINKS with routes, icons, badges) and any collapse behaviour note. These are the definitive designs — building a sub-module means building its sidebar from its entry here, and a sidebar change means updating the entry in the same PR.
