# SparesPro ERP — Documentation

> Comprehensive ERP system for motor spares businesses. Built on Laravel 12, Inertia.js, and React.

---

## Documentation Index

| Section | File | Description |
|---------|------|-------------|
| **Architecture** | [architecture.md](architecture.md) | System design, tech stack, module structure |
| **Data Sources** | [data-sources.md](data-sources.md) | Car brands, part numbers, APIs, catalogues |
| **Database Conventions** | [coding-standards/database.md](coding-standards/database.md) | Naming, migrations, schema patterns |

### Modules

| # | Module | File | Status |
|---|--------|------|--------|
| 1 | Inventory Management | [modules/01-inventory-management.md](modules/01-inventory-management.md) | Planned |
| 2 | Sales & Point of Sale | [modules/02-sales-pos.md](modules/02-sales-pos.md) | Planned |
| 3 | Purchasing & Procurement | [modules/03-purchasing-procurement.md](modules/03-purchasing-procurement.md) | Planned |
| 4 | Workshop Management | [modules/04-workshop-management.md](modules/04-workshop-management.md) | Planned |
| 5 | Customer Management | [modules/05-customer-management.md](modules/05-customer-management.md) | Planned |
| 6 | Supplier Management | [modules/06-supplier-management.md](modules/06-supplier-management.md) | Planned |
| 7 | Finance & Accounts | [modules/07-finance-accounts.md](modules/07-finance-accounts.md) | Planned |
| 8 | Vehicle & Parts Reference | [modules/08-vehicle-parts-reference.md](modules/08-vehicle-parts-reference.md) | Planned |
| 9 | Reports & Analytics | [modules/09-reports-analytics.md](modules/09-reports-analytics.md) | Planned |
| 10 | System Administration | [modules/10-system-administration.md](modules/10-system-administration.md) | Existing |

### Coding Standards

| File | Description |
|------|-------------|
| [coding-standards/php-laravel.md](coding-standards/php-laravel.md) | PSR-12, service layer, Eloquent patterns |
| [coding-standards/react-inertia.md](coding-standards/react-inertia.md) | Component patterns, hooks, Inertia usage |
| [coding-standards/database.md](coding-standards/database.md) | Migrations, naming, relationships |
| [coding-standards/git-workflow.md](coding-standards/git-workflow.md) | Branching strategy, commit messages |

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
