# SparesPro ERP — Documentation

> Comprehensive ERP system for motor spares businesses. Built on Laravel 12, Inertia.js, and React.

---

## Documentation Index

> **Start here → [AGENTS.md](../AGENTS.md)** (rules + doc navigation) → [implementation-plan.md](implementation-plan.md) (build order) → [tasks/](tasks/README.md) (living checklists).

| Section | File | Description |
|---------|------|-------------|
| **Implementation Plan** | [implementation-plan.md](implementation-plan.md) | Build order (phases) + why; links every tasks file |
| **Tasks** | [tasks/README.md](tasks/README.md) | Living checklists per module — update as work completes |
| **Testing Strategy** | [testing-strategy.md](testing-strategy.md) | Definition of done: unit/feature/golden-flow/functional |
| **Architecture** | [architecture.md](architecture.md) | System design, tech stack, module structure |
| **Configuration Centre** | [configuration-centre.md](configuration-centre.md) | No hardcoding: settings registry, per-branch overrides, document identity reuse |
| **Data Strategy (adopted)** | [integrations/local-data-seeding.md](integrations/local-data-seeding.md) | Full local seeding & imports — zero paid integrations; SA/Zim car parc packs |
| **Data Strategy (future option)** | [integrations/vehicle-parts-data-strategy.md](integrations/vehicle-parts-data-strategy.md) | Optional paid TECDOC path — not adopted for v1 |
| **Data Sources** | [data-sources.md](data-sources.md) | Reference: catalogues, free APIs, barcode standards |
| **Workflows** | [workflows/README.md](workflows/README.md) | End-to-end business processes (order-to-cash, procure-to-pay…) |
| **Design** | [design/README.md](design/README.md) | Design system, UI/HCI rules, navigation, module menus, screen designs |
| **Operations** | [operations/](operations/) | Multi-currency (USD/ZWG/ZAR), POS hardware & printing, backups & power resilience, data migration |
| **System Health** ★ | [operations/system-health.md](operations/system-health.md) | The integrity dashboard: books balanced, stock true, queues flowing — proven daily |
| **Glossary** | [glossary.md](glossary.md) | One vocabulary for docs, UI and code |
| **Database Conventions** | [coding-standards/database.md](coding-standards/database.md) | Naming, migrations, schema patterns |

### Modules

Each module has an overview doc, plus a folder of the same name containing one dedicated file **per sub-module** (workflow, screens, fields & validation, business rules, edge cases).

| # | Module | Overview | Sub-module docs | Status |
|---|--------|----------|-----------------|--------|
| 1 | Inventory Management | [01-inventory-management.md](modules/01-inventory-management.md) | [8 files](modules/01-inventory-management/) | Planned |
| 2 | Sales & Point of Sale | [02-sales-pos.md](modules/02-sales-pos.md) | [9 files](modules/02-sales-pos/) | Planned |
| 3 | Purchasing & Procurement | [03-purchasing-procurement.md](modules/03-purchasing-procurement.md) | [8 files](modules/03-purchasing-procurement/) | Planned |
| 4 | Workshop Management | [04-workshop-management.md](modules/04-workshop-management.md) | [8 files](modules/04-workshop-management/) | Planned |
| 5 | Customer Management | [05-customer-management.md](modules/05-customer-management.md) | [8 files](modules/05-customer-management/) | Planned |
| 6 | Supplier Management | [06-supplier-management.md](modules/06-supplier-management.md) | [5 files](modules/06-supplier-management/) | Planned |
| 7 | Finance & Accounts | [07-finance-accounts.md](modules/07-finance-accounts.md) | [8 files](modules/07-finance-accounts/) | Planned |
| 8 | Vehicle & Parts Reference | [08-vehicle-parts-reference.md](modules/08-vehicle-parts-reference.md) | [7 files](modules/08-vehicle-parts-reference/) | Planned |
| 9 | Reports & Analytics | [09-reports-analytics.md](modules/09-reports-analytics.md) | [8 files](modules/09-reports-analytics/) | Planned |
| 10 | System Administration | [10-system-administration.md](modules/10-system-administration.md) | [12 files](modules/10-system-administration/) | Existing |

### Coding Standards

| File | Description |
|------|-------------|
| [coding-standards/php-laravel.md](coding-standards/php-laravel.md) | PSR-12, service layer, Eloquent patterns |
| [coding-standards/react-inertia.md](coding-standards/react-inertia.md) | Component patterns, hooks, Inertia usage |
| [coding-standards/database.md](coding-standards/database.md) | Migrations, naming, relationships |
| [coding-standards/git-workflow.md](coding-standards/git-workflow.md) | Branching strategy, commit messages |
| [coding-standards/debugging-standards.md](coding-standards/debugging-standards.md) | Exceptions, logging, bug workflow, integrity self-checks |

### Design & UX

| File | Description |
|------|-------------|
| [design/design-system.md](design/design-system.md) | Colour tokens, typography, components |
| [design/ui-rules.md](design/ui-rules.md) | Binding UI/HCI rules — speed budgets, keyboard-first, error prevention, linked navigation |
| [design/component-standards.md](design/component-standards.md) | Systematic specs: buttons, modals, tables, forms, one sidebar theme |
| [design/page-guide.md](design/page-guide.md) | Collapsible in-app page guide (`F1` help) on every page |
| [design/global-search-and-quick-actions.md](design/global-search-and-quick-actions.md) | `Ctrl+K` command palette: find & act on anything |
| [design/navigation-and-layout.md](design/navigation-and-layout.md) | Dashboard → module sidebar → page archetypes |
| [design/module-menus.md](design/module-menus.md) | Level-2 module sidebar menus |
| [design/sidebars/README.md](design/sidebars/README.md) | Level-3: dedicated sidebar layout per sub-module (all 82) |
| [design/screen-designs.md](design/screen-designs.md) | Wireframes: POS, GRN, job card, part detail, stock take… |

### Operations

| File | Description |
|------|-------------|
| [operations/multi-currency.md](operations/multi-currency.md) | USD/ZWG/ZAR: dual display, multi-currency tender, FX postings |
| [operations/hardware-and-printing.md](operations/hardware-and-printing.md) | Scanners, thermal receipts, cash drawers, labels |
| [operations/backup-and-resilience.md](operations/backup-and-resilience.md) | Backups (3-2-1), UPS/load-shedding, offline-first guarantees |
| [operations/data-migration.md](operations/data-migration.md) | Onboarding an existing business: opening stock, balances, cutover |

---

## Dashboard Module Cards

The main dashboard (`resources/js/Pages/Dashboard.jsx`) uses a 4-column card grid. Replace the existing school-management cards with these:

| Icon | Module | Route Name | Description |
|------|--------|------------|-------------|
| 📦 | Inventory Management | `inventory.index` | Parts catalogue, stock control, bin locations |
| 🛒 | Sales & POS | `sales.index` | Counter sales, quotations, invoicing |
| 🏭 | Purchasing | `purchasing.index` | Purchase orders, receiving, supplier invoices |
| 🔧 | Workshop | `workshop.index` | Job cards, labour, vehicle service history |
| 👥 | Customers | `customers.index` | Customer profiles, trade accounts, vehicles |
| 🚚 | Suppliers | `suppliers.index` | Supplier profiles, price lists, performance |
| 💰 | Finance & Accounts | `finance.index` | GL, AR, AP, bank management |
| 🚗 | Vehicle Reference | `vehicle-reference.index` | Makes, models, parts cross-reference |
| 📊 | Reports & Analytics | `reports.index` | Sales, stock, financial, KPI dashboards |
| ⚙️ | System Administration | `auth.users.index` | Users, roles, company setup (existing) |

---

## Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend framework | Laravel | 12.x |
| Frontend adapter | Inertia.js | 2.x |
| Frontend framework | React | 18.x |
| Module architecture | nwidart/laravel-modules | 12.x |
| CSS framework | Tailwind CSS | 4.x |
| Component library | shadcn/ui (Radix + CVA) | Latest |
| Icons | Lucide React | 0.545+ |
| Named routes (JS) | Ziggy | 2.x |
| Build tool | Vite | 7.x |
| PHP version | PHP | 8.2+ |
| Database | MySQL / PostgreSQL | 8.0+ / 15+ |

---

## Module Architecture (nwidart)

Each module lives under `Modules/` and is self-contained:

```
Modules/
  InventoryManagement/
    app/
      Http/Controllers/
      Http/Requests/
      Http/Resources/
      Models/
      Policies/
      Services/
      Events/
      Listeners/
    config/
    database/migrations/
    database/seeders/
    resources/js/Pages/
    resources/js/Components/
    routes/web.php
    module.json
```
