# System Architecture

> Technical architecture for SparesPro ERP. Laravel 12 monolith with modular structure, Inertia.js SPA behaviour, React frontend.

---

## Technology Stack

| Layer | Technology | Version | Role |
|-------|-----------|---------|------|
| Backend | Laravel | 12.x | HTTP, business logic, DB |
| Module system | nwidart/laravel-modules | 12.x | Domain isolation |
| Frontend adapter | Inertia.js | 2.x | SPA without a separate API |
| Frontend | React | 18.x | UI components |
| CSS | Tailwind CSS | 4.x | Styling |
| Component library | shadcn/ui style (Radix + CVA) | Latest | Accessible UI primitives |
| Icons | Lucide React | 0.545+ | |
| Routes in JS | Ziggy | 2.x | Named Laravel routes in JS |
| Build | Vite | 7.x | Asset bundling |
| Database | MySQL 8.0+ or PostgreSQL 15+ | | Primary data store |
| Queue | Laravel Queue (database driver) | | Background jobs |
| PDF | barryvdh/laravel-dompdf | | Document printing |
| Excel | maatwebsite/laravel-excel | | Import/export |
| PHP | PHP | 8.2+ | |

---

## Architecture Pattern

```
Browser (React + Inertia)
         │  HTTP requests (full page / partial)
         ▼
Laravel Router → Middleware → Controller
                                   │
                          Form Request (validation)
                                   │
                          Policy (authorisation)
                                   │
                          Service (business logic)
                              │        │
                          Models    Events → Listeners → Jobs
                              │
                          Database (MySQL)
                                   │
                          Inertia Response → React Page Component
```

### Key Architectural Decisions

**Why a monolith, not microservices?**
A motor spares business is a single operational unit. Microservices add complexity without benefit at this scale. A well-structured Laravel monolith with nwidart modules gives domain isolation while keeping deployment simple.

**Why Inertia, not a separate API + React SPA?**
- No need to maintain a separate API
- Authentication is server-side (no JWT complexity)
- Progressive enhancement (server-side rendering for SEO, though not critical for ERP)
- Forms and navigation are simpler
- One codebase to deploy

**Why nwidart modules?**
- Each business domain is encapsulated: routes, models, controllers, migrations, views all in one folder
- New modules can be built and enabled independently
- Teams can work on different modules without conflicts
- Easy to disable a module without affecting others

---

## Module Dependency Map

```
                    ┌──────────────────┐
                    │  System Admin    │ (no dependencies)
                    └──────────┬───────┘
                               │ provides
                    ┌──────────▼───────┐
                    │   Users / Roles  │
                    └──────────┬───────┘
                               │ used by all modules
          ┌────────────────────┼────────────────────┐
          │                    │                    │
┌─────────▼──────┐  ┌─────────▼──────┐  ┌─────────▼──────┐
│  Vehicle Ref   │  │  Customers     │  │  Suppliers     │
└────────┬───────┘  └────────┬───────┘  └────────┬───────┘
         │                   │                    │
         └──────────┬────────┘                    │
                    │                             │
         ┌──────────▼──────────┐    ┌─────────────▼──────┐
         │  Inventory Mgmt     │◄───│  Purchasing        │
         └──────────┬──────────┘    └────────────────────┘
                    │
         ┌──────────┴──────────┐
         │                     │
┌────────▼──────┐    ┌─────────▼──────┐
│  Sales & POS  │    │  Workshop      │
└───────┬───────┘    └────────┬───────┘
        │                     │
        └──────────┬──────────┘
                   │
          ┌────────▼────────┐
          │ Finance &       │
          │ Accounts        │
          └────────┬────────┘
                   │
          ┌────────▼────────┐
          │ Reports &       │
          │ Analytics       │
          └─────────────────┘
```

---

## Multi-Branch Architecture

SparesPro supports multiple physical branches (stores/locations).

### How Branches Work
- Every transaction is tagged to a `branch_id`
- Stock is tracked per branch (separate `stock_levels` row per part per branch)
- Users are assigned to a branch as their "home branch" but can be granted access to other branches
- Reports default to the logged-in user's branch, with manager override to view all

### Branch Isolation Rules
- A cashier at Branch A cannot post transactions against Branch B stock
- A branch manager can only see their branch's transactions and reports
- A "super admin" or "director" role sees all branches
- `HandleInertiaRequests` middleware injects the user's current branch into all Inertia shared data

---

## Authentication & Authorisation

### Authentication
- Laravel's built-in session authentication
- No JWT (Inertia handles session naturally)
- Password change enforcement on first login (`EnsurePasswordIsChanged` middleware — already implemented)
- Configurable password policy (complexity, expiry — already implemented)

### Authorisation: Route-Based RBAC
The existing system stores permissions as `SystemRoute` records (route names) linked to `Role`s via a pivot. This is checked in the `EnsureHasRole` middleware.

**Extension for module-level permissions:**
Each new module seeds its `SystemRoute` records. The admin can assign these routes to roles via the Bulk Assign interface.

```php
// Seeding permissions for Inventory Management
SystemModule::updateOrCreate(['prefix' => 'inventory'], [
    'name' => 'Inventory Management',
    'icon' => 'package',
    'description' => 'Parts catalogue, stock control, and bin locations',
    'order' => 1,
    'status' => true,
]);

$inventoryRoutes = [
    ['name' => 'inventory.parts.index',  'description' => 'View parts list'],
    ['name' => 'inventory.parts.create', 'description' => 'Create new part'],
    ['name' => 'inventory.parts.edit',   'description' => 'Edit parts'],
    ['name' => 'inventory.parts.destroy','description' => 'Delete parts'],
    ['name' => 'inventory.stock.index',  'description' => 'View stock levels'],
    // ...
];
```

---

## Data Flow: Sale Transaction (Example)

```
1. Cashier scans barcode at POS
   → Part looked up in parts table (via barcode_ean or barcode_code128)
   → Stock availability checked (stock_levels.qty_available)
   → Customer price list applied

2. Cashier processes payment
   → sales_documents record created (document_type='invoice', status='draft')
   → sales_document_lines records created
   → Validation: credit limit check (if on-account)
   → Invoice posted:
       a. sales_documents.status → 'complete'
       b. stock_ledger entry created (SALE, -qty, unit_cost)
       c. stock_levels.qty_on_hand decremented
       d. gl_journal created:
          DR: Cash/Bank (or Debtors if on-account)   = invoice total incl VAT
          CR: Sales Revenue (by category)             = invoice total excl VAT
          CR: VAT Output                              = VAT amount
       e. payments record created

3. Receipt printed (thermal or A4)
4. Flash message: "Invoice INV-20260101-0042 — $94.30"
```

---

## Printing Architecture

### Document PDF Generation
All printed documents use Blade templates rendered to PDF via DomPDF:

```php
class InvoicePdfController extends Controller
{
    public function download(SalesDocument $invoice): Response
    {
        $pdf = Pdf::loadView('print.invoice', [
            'invoice'  => $invoice->load('lines.part', 'customer', 'branch'),
            'company'  => Company::first(),
        ]);

        return $pdf->download("Invoice-{$invoice->document_number}.pdf");
    }
}
```

### Thermal Receipt (80mm)
A separate Blade template for thermal printers — narrow layout, minimal styling, optimised for monospace font.

---

## Queue & Background Jobs

### Recommended Queue Setup

**Development:** `QUEUE_CONNECTION=sync` — runs jobs synchronously (no worker needed)

**Production:** `QUEUE_CONNECTION=database` — jobs stored in the `jobs` table

Run the queue worker on the server:
```bash
php artisan queue:work --queue=default,imports,emails --tries=3
```

### Jobs Used

| Job | Queue | Trigger |
|-----|-------|---------|
| `SendInvoiceEmail` | emails | Invoice created |
| `SendStatementEmail` | emails | Month-end batch |
| `SendLowStockAlert` | default | Stock below reorder |
| `ProcessSupplierPriceListImport` | imports | Price list uploaded |
| `GenerateScheduledReport` | default | Scheduled report due |
| `ProcessBankStatementImport` | imports | Bank statement uploaded |
| `SendJobCardCompletionSms` | default | Job card completed |

---

## File Storage

```
storage/app/
  parts/
    images/           ← Part images
  documents/
    invoices/         ← Generated invoice PDFs
    purchase_orders/  ← PO PDFs
    job_cards/        ← Job card PDFs
  imports/
    price_lists/      ← Uploaded supplier price lists (pre-processing)
    bank_statements/  ← Uploaded bank statement files
  exports/
    reports/          ← Generated report exports
```

Use Laravel's `Storage` facade. In development, use the `local` disk. In production, configure `s3` or similar for scalability.

---

## Performance Considerations

| Concern | Solution |
|---------|---------|
| Large parts catalogue search | Full-text index on `parts.description`, `parts.part_number`, `parts.oem_number` |
| Stock level queries | Indexed on `(part_id, branch_id)` — covered index for most queries |
| Dashboard KPIs | Partial Inertia reloads; consider Redis caching for heavy aggregations |
| Stock ledger | Append-only table grows large. Archive entries older than 3 years to `stock_ledger_archive` |
| Report generation | Queue long reports; return a job ID; poll for completion |
| Images | Lazy-load in parts list; serve from CDN or S3 in production |

---

## Deployment

### Minimum Server Requirements
| Component | Requirement |
|-----------|------------|
| PHP | 8.2+ with extensions: pdo_mysql, mbstring, xml, curl, zip |
| Database | MySQL 8.0+ or PostgreSQL 15+ |
| Web server | Nginx or Apache |
| Node (build only) | 20+ |
| Memory | 2GB RAM minimum, 4GB recommended |
| Storage | 20GB+ (grows with images and documents) |

### Environment-Specific Config
- `APP_ENV=production` disables debug mode and error details
- `APP_DEBUG=false` in production
- Run `php artisan config:cache` and `php artisan route:cache` after deployment
- Run `php artisan queue:restart` after deployment if using queues
