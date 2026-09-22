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

Entering a module swaps to a module-specific layout: the `AdminLayout` pattern (fixed dark `bg-slate-900` w-64 sidebar + top bar) generalised into a `ModuleLayout` that takes a nav config. The existing `FinanceSidebar.jsx` shows the pattern — one sidebar per module, sections mirroring the sub-modules.

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
- Sidebar items are permission-filtered like the cards.
- Bottom of every sidebar: **Return Home** (dashboard) and **Logout** — as in the current `AdminLayout`.
- Top bar: breadcrumb (`Module › Sub-module › Record`), global part-search (`F2` from anywhere in the module), notifications, profile menu, current branch badge.
- Nav configs live per module in `Modules/{X}/resources/js/nav.js` exporting `[{ section, items: [{ label, route, icon, permission }] }]` — the sidebar component is shared, the config is not.

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
