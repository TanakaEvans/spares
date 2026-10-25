# Navigation & Layout Model

> The three-level shell: Dashboard (module cards) → Module workspace (module sidebar) → Page. Extends the existing `AppLayout` / `AdminLayout` pattern in `resources/js/Layouts/`.

---

## Level 1 — Dashboard (module cards)

`resources/js/Pages/Dashboard.jsx`, wrapped in `AppLayout` (header-only shell). Replaces the current school-management cards.

```
┌──────────────────────────────────────────────────────────────┐
│  SparesPro   [branch: Harare Main ▾]        [🔔] [user ▾]    │
├──────────────────────────────────────────────────────────────┤
│   Good morning, Tanaka — Tuesday 22 Sep                       │
│   ┌─ Today's sales ─┐ ┌─ Open jobs ─┐ ┌─ Low stock ─┐        │  ← 3 mini-KPIs
│                                                               │
│   ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐        │
│   │ 📦        │ │ 🛒        │ │ 🏭        │ │ 🔧        │      │
│   │ Inventory │ │ Sales &  │ │ Purchas- │ │ Workshop │        │
│   │ Mgmt     │ │ POS      │ │ ing      │ │          │        │
│   └──────────┘ └──────────┘ └──────────┘ └──────────┘        │
│   ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐        │
│   │ 👥 Cust- │ │ 🚚 Supp- │ │ 💰 Fin-  │ │ 🚗 Veh.  │        │
│   │ omers    │ │ liers    │ │ ance     │ │ Reference│        │
│   └──────────┘ └──────────┘ └──────────┘ └──────────┘        │
│   ┌──────────┐ ┌──────────┐                                   │
│   │ 📊 Rep-  │ │ ⚙️ System │                                   │
│   │ orts     │ │ Admin    │                                   │
│   └──────────┘ └──────────┘                                   │
└──────────────────────────────────────────────────────────────┘
```

- Grid: `grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4` (same as current).
- Card: Lucide icon (not emoji in the real UI), title, one-line description, `Active` / `Coming Soon` badge — same card component as today.
- **Cards are permission-filtered**: a user only sees modules containing at least one route their role grants (from `system_modules` / `system_routes`).
- Card order = `system_modules.order`; icons from `system_modules.icon` (Lucide name).

| # | Card | Lucide icon | Route |
|---|------|-------------|-------|
| 1 | Inventory Management | `package` | `inventory.index` |
| 2 | Sales & POS | `shopping-cart` | `sales.pos` |
| 3 | Purchasing | `factory` | `purchasing.index` |
| 4 | Workshop | `wrench` | `workshop.jobs.index` |
| 5 | Customers | `users` | `customers.index` |
| 6 | Suppliers | `truck` | `suppliers.index` |
| 7 | Finance & Accounts | `banknote` | `finance.index` |
| 8 | Vehicle Reference | `car` | `vehicle-ref.fitment` |
| 9 | Reports & Analytics | `bar-chart-3` | `reports.dashboard` |
| 10 | System Administration | `settings` | `auth.users.index` (existing) |

---

## Level 2 — Module workspace (module sidebar)

Entering a module (its landing page included) renders the shared `ModuleLayout` — fixed dark `bg-slate-900` sidebar + top bar — driven by that module's nav config in `resources/js/nav/{module}.js` (registry: `resources/js/nav/index.js`).

### §2 The stable-menu rule (revised 2026-10-25 after user feedback)

Users reported the sidebar "constantly changing": the original Level-3 design swapped the whole module menu for a per-sub-module menu on every hop, and the module landing had no sidebar at all. Replaced by one rule — **the menu is identical on every page of a module; nothing is ever swapped in or out.**

```
┌──────────────────────────┐
│ [▣] Inventory Mgmt   ⇅   │ ← module switcher (click → any module / Dashboard)
│ [🔍 Search…     Ctrl K]  │ ← opens the command palette
│ ⊞ Overview               │ ← module landing (sub-module cards)
│ CATALOGUE              ▾ │ ← collapsible section, state remembered
│ ▍Parts                   │ ← active = longest route-prefix match
│     + New Part           │ ← permanent quick action (children)
│   Categories             │
│ STOCK                  ▾ │
│   Stock Levels …         │
│ ──────────────────────── │
│ RELATED ⇄                │ ← cross-module jumps for the current item only,
│   Fitment Lookup         │   always at the bottom so nothing above moves
│ ──────────────────────── │
│ ⌂ Dashboard · « Collapse · ⎋ Logout │
└──────────────────────────┘
```

Config shape (content only):
```js
export default {
  moduleKey, moduleLabel, moduleIcon,
  sections: [{ section, items: [{
    label, route, icon,
    match?,     // route-name prefix that makes it active (default: route minus ".index")
    children?,  // quick actions always shown under the item
    related?,   // cross-module jumps, shown at the bottom while this item is active (max 4)
  }] }],
};
```

Behaviour:
- **Active item** = the longest matching route-name prefix (`inventory.stock-takes.show` → Stock Takes, never Stock Levels; `customers.groups.*` beats the `customers` catch-all). A child (e.g. *New Part*) highlights instead of its parent while you are on it.
- **Sections** collapse and remember it per module (`localStorage nav.closed.{module}`); the section holding the current page can't be collapsed.
- **Scroll position** of the sidebar survives navigation (`sessionStorage`), and the active item is scrolled into view if needed.
- **Icon rail**: `[` (or the Collapse button) shrinks the sidebar to icons, remembered across sessions — for POS/receiving users who want width.
- **Hover prefetch** on every item (Inertia `prefetch="hover"`, 30 s cache) — pages arrive before the click.
- **Cross-module screens never appear as menu items** — only under *Related ⇄*, because following them changes the menu to the other module's.
- Permission-filtered; empty sections are omitted.
- `NavigationCoverageTest` enforces: every menu route exists, every screen is reachable from a menu (exceptions listed with reasons), every module has a registered config.

```
┌────────┬─────────────────────────────────────────────────────┐
│ 📦     │  Inventory › Parts            [search]  [🔔] [user] │ ← top bar
│ INVEN- ├─────────────────────────────────────────────────────┤
│ TORY   │                                                     │
│        │                 (page content)                      │
│ Parts  │                                                     │
│ Stock  │                                                     │
│ Bins   │                                                     │
│ Takes  │                                                     │
│ Reorder│                                                     │
│ Fitment│                                                     │
│ ────── │                                                     │
│ ⌂ Home │  ← always: back to dashboard                        │
└────────┴─────────────────────────────────────────────────────┘
```

Rules:
- Sidebar sections = the module's sub-modules, in doc order. Active item = accent-coloured left border + `bg-primary-light`.
- ~~Level 3 context switch~~ — **retired 2026-10-25** (see §2). The per-sub-module designs in [sidebars/](sidebars/README.md) remain the source for each item's quick actions (`children`) and cross-module jumps (`related`), but they no longer replace the module menu.
- Sidebar items are permission-filtered like the cards.
- Bottom of every sidebar: **Dashboard**, **Collapse** and **Logout**.
- Top bar: breadcrumb (`Module › Sub-module › Record`), notifications, page guide, user.
- Nav configs live in `resources/js/nav/{module}.js` — the sidebar component is shared, the config is not.

---

## Level 3 — Page patterns

Four page archetypes; every screen is one of them (specifics in [screen-designs.md](screen-designs.md)):

| Archetype | Layout | Examples |
|-----------|--------|----------|
| **List** | Header (title + primary action) → filter row → `DataTable` → pagination | Parts, Invoices, Job cards |
| **Form** | Header → card(s) of `FormField`s, 2-col grid → sticky footer (Cancel / Save) | Create part, New customer |
| **Detail** | Header (identity + status badge + actions) → summary strip → tabs | Part detail, Customer profile, Job card |
| **Operational** | Full-width split-pane, keyboard-first, minimal chrome | POS, GRN receiving, Stock take count |

Shared conventions:
- Primary action is one accent button, top-right of the header ("New Part", "Receive Goods").
- Detail tabs load via Inertia partial reloads (`only: [...]`) — switching tabs never refetches the whole page.
- Breadcrumbs always mirror route nesting; the record segment shows the human number (INV-20260922-0042), not the ID.
- Unsaved-changes guard on all Form pages (Inertia `useForm.isDirty` + confirm on navigate).

---

## URL structure

```
/dashboard                          Level 1
/inventory                          module landing → redirects to parts index
/inventory/parts                    list
/inventory/parts/create             form
/inventory/parts/{part}             detail
/inventory/parts/{part}/edit        form
/sales/pos                          operational
/workshop/jobs/{job}                detail (tabs: overview, labour, parts, costing, history)
```

Route names follow `module.resource.action` throughout (Ziggy exposes them to React) — this exact name is also the permission key in `system_routes`.
