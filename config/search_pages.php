<?php

/*
|--------------------------------------------------------------------------
| Command Palette — page registry (Phase 0 sources)
|--------------------------------------------------------------------------
| Pages searchable via Ctrl+K. Entity sources (parts, customers, documents,
| vehicles) register here as their modules land (docs/design/
| global-search-and-quick-actions.md). Keywords are matched alongside label.
*/

return [
    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'layout-dashboard', 'keywords' => 'home modules start'],
    ['label' => 'Configuration Centre', 'route' => 'admin.settings.index', 'icon' => 'sliders-horizontal', 'keywords' => 'settings config options vat discount thresholds'],
    ['label' => 'Currencies & Rates', 'route' => 'admin.currencies.index', 'icon' => 'coins', 'keywords' => 'exchange rate usd zwg zar forex money'],
    ['label' => 'Number Sequences', 'route' => 'admin.sequences.index', 'icon' => 'hash', 'keywords' => 'invoice numbering document numbers prefix'],
    ['label' => 'Company Details', 'route' => 'admin.company.index', 'icon' => 'building-2', 'keywords' => 'logo vat banking identity'],
    ['label' => 'Branches', 'route' => 'admin.branches.index', 'icon' => 'map-pin', 'keywords' => 'locations stores multi branch'],
    ['label' => 'Departments', 'route' => 'admin.departments.index', 'icon' => 'folder-kanban', 'keywords' => 'org structure sections'],
    ['label' => 'Employees', 'route' => 'admin.employees.index', 'icon' => 'id-card', 'keywords' => 'staff workers'],
    ['label' => 'Users', 'route' => 'auth.users.index', 'icon' => 'users', 'keywords' => 'accounts logins people access'],
    ['label' => 'Roles & Permissions', 'route' => 'auth.roles.index', 'icon' => 'user-cog', 'keywords' => 'rbac access rights security'],
    ['label' => 'Activity Logs', 'route' => 'system.logs', 'icon' => 'scroll-text', 'keywords' => 'audit history who did what'],
    ['label' => 'Documentation', 'route' => 'help.docs.show', 'icon' => 'book-open', 'keywords' => 'help docs manual guide specs'],
    ['label' => 'Inventory Management', 'route' => 'modules.show', 'params' => ['module' => 'inventory'], 'icon' => 'package', 'keywords' => 'parts stock bins'],
    ['label' => 'Sales & POS', 'route' => 'modules.show', 'params' => ['module' => 'sales'], 'icon' => 'shopping-cart', 'keywords' => 'sell invoice quote till counter'],
    ['label' => 'Purchasing', 'route' => 'modules.show', 'params' => ['module' => 'purchasing'], 'icon' => 'factory', 'keywords' => 'buy orders grn receive'],
    ['label' => 'Workshop', 'route' => 'modules.show', 'params' => ['module' => 'workshop'], 'icon' => 'wrench', 'keywords' => 'jobs repairs labour technicians'],
    ['label' => 'Customers', 'route' => 'modules.show', 'params' => ['module' => 'customers'], 'icon' => 'users', 'keywords' => 'debtors accounts crm'],
    ['label' => 'Suppliers', 'route' => 'modules.show', 'params' => ['module' => 'suppliers'], 'icon' => 'truck', 'keywords' => 'creditors vendors'],
    ['label' => 'Finance & Accounts', 'route' => 'modules.show', 'params' => ['module' => 'finance'], 'icon' => 'banknote', 'keywords' => 'gl ledger accounting vat books'],
    ['label' => 'Vehicle Reference', 'route' => 'modules.show', 'params' => ['module' => 'vehicle-reference'], 'icon' => 'car', 'keywords' => 'makes models fitment engines'],
    ['label' => 'Reports & Analytics', 'route' => 'modules.show', 'params' => ['module' => 'reports'], 'icon' => 'bar-chart-3', 'keywords' => 'kpi dashboards analysis'],
    ['label' => 'System Administration', 'route' => 'modules.show', 'params' => ['module' => 'system-admin'], 'icon' => 'settings', 'keywords' => 'admin setup'],
];
