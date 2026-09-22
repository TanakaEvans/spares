# Module Sidebar Menus

> The definitive sidebar design for each module. **Rule: every module has its own sidebar; every sub-module is reachable from it.** Rendered by the shared `ModuleLayout` from a per-module `nav.js` config (see [navigation-and-layout.md](navigation-and-layout.md)); items are permission-filtered; icons are Lucide names.

**Config shape** (`Modules/{X}/resources/js/nav.js`):
```js
export default [
  { section: 'Section label', items: [
    { label: 'Item', route: 'module.resource.index', icon: 'lucide-name', permission: 'module.resource.index' },
  ]},
];
```
Every sidebar ends with the fixed block: **⌂ Dashboard · ⚙ Module Settings (deep-link into Configuration Centre group) · ⎋ Logout**.

---

## 📦 1 Inventory Management
```
OVERVIEW
  Module Home ............... inventory.index (KPIs: stock value, low stock, dead stock)
CATALOGUE
  Parts ..................... inventory.parts.index          (package-search)
  Categories ................ inventory.categories.index     (folder-tree)
  Brands .................... inventory.brands.index         (tags)
STOCK
  Stock Levels .............. inventory.stock.index          (layers)
  Adjustments ............... inventory.adjustments.index    (sliders-horizontal)
  Transfers ................. inventory.transfers.index      (arrow-left-right)
  Bin Locations ............. inventory.bins.index           (map-pin)
COUNTING
  Stock Takes ............... inventory.stocktakes.index     (clipboard-check)
REPLENISHMENT
  Reorder Report ............ inventory.reorder              (refresh-cw)
FITMENT
  Fitment Guide ............. inventory.fitments.index       (car)
  Serial/Batch (Ph6) ........ inventory.serials.index        (scan-barcode)
TOOLS
  Import Parts .............. inventory.imports.create       (upload)
```

## 🛒 2 Sales & POS
```
SELL
  POS / Counter Sale ........ sales.pos                      (shopping-cart)   ← default landing
  Suspended Sales ........... sales.pos.suspended            (pause-circle)
DOCUMENTS
  Quotations ................ sales.quotes.index             (file-text)
  Sales Orders .............. sales.orders.index             (clipboard-list)
  Invoices .................. sales.invoices.index           (receipt)
  Credit Notes .............. sales.credits.index            (undo-2)
  Delivery Notes (Ph6) ...... sales.deliveries.index         (truck)
PRICING
  Price Lists ............... sales.pricelists.index         (list-ordered)
  Promotions (Ph6) .......... sales.promotions.index         (percent)
OTHER
  Lay-bys (Ph6) ............. sales.laybys.index             (calendar-clock)
  Daily Summary / Z-Read .... sales.till.summary             (calculator)
```

## 🏭 3 Purchasing
```
ORDER
  Purchase Orders ........... purchasing.orders.index        (file-input)
  Requisitions (Ph6) ........ purchasing.requisitions.index  (list-plus)
RECEIVE
  Goods Receiving (GRN) ..... purchasing.grns.index          (package-check)
BILLS
  Supplier Invoices ......... purchasing.invoices.index      (file-spreadsheet)
  Supplier Credit Notes ..... purchasing.credits.index       (file-minus)
RETURNS
  Returns to Supplier ....... purchasing.returns.index       (package-x)
IMPORTS (Ph6)
  Shipments ................. purchasing.imports.index       (ship)
TOOLS
  Price Comparison (Ph6) .... purchasing.price-compare       (scale)
```

## 🔧 4 Workshop
```
JOBS
  Job Board ................. workshop.board                 (kanban-square)   ← default landing
  Job Cards ................. workshop.jobs.index            (wrench)
  New Job / Intake .......... workshop.jobs.create           (plus-circle)
VEHICLES
  Vehicle Registry .......... workshop.vehicles.index        (car-front)
PEOPLE
  Technicians ............... workshop.technicians.index     (hard-hat)
SETUP
  Labour Codes .............. workshop.labour.index          (timer)
  Labour Rates .............. workshop.rates.index           (banknote)
CLAIMS
  Warranty Claims ........... workshop.warranty.index        (shield-check)
```

## 👥 5 Customers
```
CUSTOMERS
  All Customers ............. customers.index                (users)
  Customer Groups ........... customers.groups.index         (users-round)
ACCOUNTS
  Credit Management ......... customers.credit               (gauge)
  Statements ................ customers.statements           (file-stack)
ENGAGEMENT (Ph6)
  Loyalty Programme ......... customers.loyalty              (award)
  Communications ............ customers.communications.index (message-square)
```

## 🚚 6 Suppliers
```
SUPPLIERS
  All Suppliers ............. suppliers.index                (truck)
PRICING
  Price Lists ............... suppliers.pricelists.index     (list-ordered)
  Import Price List ......... suppliers.pricelists.import    (upload)
SOURCING
  Approved Suppliers ........ suppliers.approved.index       (badge-check)
  Performance (Ph6) ......... suppliers.performance          (trending-up)
```

## 💰 7 Finance & Accounts
```
LEDGER
  GL Enquiry ................ finance.gl.index               (book-open)
  Journals .................. finance.journals.index         (pen-line)
  Chart of Accounts ......... finance.coa.index              (list-tree)
RECEIVABLES
  Receipts .................. finance.receipts.index         (hand-coins)
  AR Ageing ................. finance.ar.ageing              (hourglass)
PAYABLES
  Supplier Payments ......... finance.payments.index         (banknote)
  Payment Run ............... finance.payment-run            (rows-3)
  AP Ageing ................. finance.ap.ageing              (hourglass)
BANKING
  Bank Accounts ............. finance.bank.index             (landmark)
  Reconciliation ............ finance.bank.reconcile         (git-compare)
TAX
  VAT Returns ............... finance.vat.index              (percent)
CLOSE
  Periods & Years ........... finance.periods.index          (calendar)
  Month-End Close ........... finance.close                  (lock)
STATEMENTS
  Trial Balance ............. finance.reports.trial-balance  (scale)
  Income Statement .......... finance.reports.pl             (trending-up)
  Balance Sheet ............. finance.reports.balance-sheet  (columns-2)
```

## 🚗 8 Vehicle Reference
```
LOOKUP
  Fitment Lookup ............ vehicle-ref.fitment            (search)   ← default landing
  Cross-Reference Search .... vehicle-ref.cross-ref          (shuffle)
DATA
  Makes ..................... vehicle-ref.makes.index        (factory)
  Models & Variants ......... vehicle-ref.models.index       (car)
  Engine Codes .............. vehicle-ref.engines.index      (cog)
  Supersessions ............. vehicle-ref.supersessions.index (git-branch)
  Technical Bulletins (Ph6) . vehicle-ref.bulletins.index    (alert-triangle)
TOOLS
  Import Fitments ........... vehicle-ref.imports.create     (upload)
```

## 📊 9 Reports & Analytics
```
DASHBOARD
  Executive Dashboard ....... reports.dashboard              (layout-dashboard)  ← default landing
REPORT SUITES
  Sales ..................... reports.sales.index            (shopping-cart)
  Inventory ................. reports.inventory.index        (package)
  Financial ................. reports.finance.index          (banknote)
  Customers ................. reports.customers.index        (users)
  Suppliers ................. reports.suppliers.index        (truck)
  Workshop .................. reports.workshop.index         (wrench)
AUTOMATION
  Scheduled Reports ......... reports.scheduled.index        (clock)
```

## ⚙️ 10 System Administration  *(existing sidebar, reorganised)*
```
ORGANISATION
  Company Details ........... admin.company.index
  Branches .................. admin.branches.index
  Departments / Sections .... admin.departments.index
ACCESS
  Users ..................... auth.users.index
  Roles & Permissions ....... auth.roles.index
  Bulk Assign / Remove ...... auth.roles.bulk
CONFIGURATION
  Configuration Centre ...... admin.settings.index           ← the settings registry UI
  Currencies & Rates ........ admin.currencies.index
  Number Sequences .......... admin.sequences.index
  Print Templates ........... admin.templates.index
  Email & SMS ............... admin.email.index
GOVERNANCE
  System Health ............. admin.health                  ← ✓/⚠/✗ integrity dashboard
  Activity Logs ............. admin.logs.index
  Backups ................... admin.backup.index
  Password Policy ........... admin.password-policy
  Notifications ............. admin.notifications.routing   ← routing matrix + delivery log
  Page Guides ............... admin.page-guides.index       ← local guide overrides
```

---

### Behaviour rules
1. `(Ph6)` items render greyed with a "Coming soon" tag until the feature ships (driven by `system_routes.status`) — the menu structure is stable from day one so muscle memory never breaks.
2. Active item: accent left-border + light background; its section auto-expands; sections are collapsible and remember state per user (`localStorage`).
3. Badge counts on operational items where noted in module docs (e.g. Reorder Report shows count of below-reorder parts; Job Board shows open jobs; GRN shows POs awaiting receipt).
4. Default landing per module is the ★-marked item, overridable per role in the Configuration Centre.
