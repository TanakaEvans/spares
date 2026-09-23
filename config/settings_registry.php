<?php

/*
|--------------------------------------------------------------------------
| Settings Registry (core)
|--------------------------------------------------------------------------
| Every configurable business value is DECLARED here (typed, grouped,
| defaulted) and VALUED in the `settings` table via SettingsService.
| Modules append their own declarations by merging into this registry
| from their service providers. See docs/configuration-centre.md.
|
| Types: string | int | decimal | percent | money | bool | select | json
| per_branch: true → a branch-level row may override the global value.
*/

return [

    // ── General ────────────────────────────────────────────────────────
    'general.date_format' => [
        'label'      => 'Date display format',
        'group'      => 'General',
        'type'       => 'select',
        'options'    => ['d/m/Y' => '22/09/2026', 'Y-m-d' => '2026-09-22', 'd M Y' => '22 Sep 2026'],
        'default'    => 'd/m/Y',
        'rules'      => ['in:d/m/Y,Y-m-d,d M Y'],
        'per_branch' => false,
        'help'       => 'How dates are shown throughout the system.',
    ],
    'general.rows_per_page' => [
        'label'      => 'Table rows per page',
        'group'      => 'General',
        'type'       => 'int',
        'default'    => 25,
        'rules'      => ['integer', 'min:10', 'max:100'],
        'per_branch' => false,
        'help'       => 'Default pagination size for list screens.',
    ],

    // ── Currency ───────────────────────────────────────────────────────
    'currency.base' => [
        'label'      => 'Base currency (immutable once trading)',
        'group'      => 'Currency',
        'type'       => 'select',
        'options'    => ['USD' => 'US Dollar (USD)', 'ZAR' => 'South African Rand (ZAR)', 'ZWG' => 'Zimbabwe Gold (ZWG)'],
        'default'    => 'USD',
        'rules'      => ['in:USD,ZAR,ZWG'],
        'per_branch' => false,
        'sensitive'  => true,
        'help'       => 'All ledgers post in this currency. Do not change after go-live.',
    ],
    'currency.dual_display' => [
        'label'      => 'Dual-currency price display (USD + ZWG)',
        'group'      => 'Currency',
        'type'       => 'bool',
        'default'    => false,
        'rules'      => ['boolean'],
        'per_branch' => true,
        'help'       => 'Show prices and totals in both currencies at the daily rate.',
    ],
    'currency.block_sales_without_daily_rate' => [
        'label'      => 'Block sales when today\'s exchange rate is missing',
        'group'      => 'Currency',
        'type'       => 'bool',
        'default'    => false,
        'rules'      => ['boolean'],
        'per_branch' => true,
        'help'       => 'Off = fall back to last known rate with a warning banner.',
    ],
    'currency.cash_rounding_step' => [
        'label'      => 'Cash rounding step',
        'group'      => 'Currency',
        'type'       => 'decimal',
        'default'    => 0.01,
        'rules'      => ['numeric', 'in:0.01,0.05,0.10,0.50,1.00'],
        'per_branch' => true,
        'help'       => 'Cash totals round to this step (coin shortages).',
    ],

    // ── Tax ────────────────────────────────────────────────────────────
    'tax.vat_rate_default' => [
        'label'      => 'Default VAT rate (%)',
        'group'      => 'Tax',
        'type'       => 'percent',
        'default'    => 15,
        'rules'      => ['numeric', 'min:0', 'max:100'],
        'per_branch' => false,
        'sensitive'  => true,
        'help'       => 'Applied when a part has no specific VAT code.',
    ],
    'tax.prices_include_vat' => [
        'label'      => 'Displayed prices include VAT',
        'group'      => 'Tax',
        'type'       => 'bool',
        'default'    => true,
        'rules'      => ['boolean'],
        'per_branch' => false,
        'help'       => 'Retail-style inclusive pricing vs trade-style exclusive.',
    ],

    // ── Inventory ──────────────────────────────────────────────────────
    'inventory.allow_negative_stock' => [
        'label'      => 'Allow negative stock',
        'group'      => 'Inventory',
        'type'       => 'bool',
        'default'    => false,
        'rules'      => ['boolean'],
        'per_branch' => true,
        'help'       => 'When off, sales that would drive stock below zero are blocked.',
    ],
    'inventory.cost_method' => [
        'label'      => 'Inventory cost method',
        'group'      => 'Inventory',
        'type'       => 'select',
        'options'    => ['avco' => 'Average cost (AVCO)', 'fifo' => 'First in, first out (FIFO)'],
        'default'    => 'avco',
        'rules'      => ['in:avco,fifo'],
        'per_branch' => false,
        'sensitive'  => true,
        'help'       => 'AVCO recommended. Do not change mid-year.',
    ],
    'inventory.stock_take_variance_recount_value' => [
        'label'      => 'Stock take variance requiring recount (value)',
        'group'      => 'Inventory',
        'type'       => 'money',
        'default'    => 50,
        'rules'      => ['numeric', 'min:0'],
        'per_branch' => true,
        'help'       => 'Line variances above this value force a second count.',
    ],
    'inventory.dead_stock_days' => [
        'label'      => 'Dead stock definition (days without movement)',
        'group'      => 'Inventory',
        'type'       => 'int',
        'default'    => 365,
        'rules'      => ['integer', 'min:30'],
        'per_branch' => false,
        'help'       => 'Used by the dead stock report and health checks.',
    ],

    // ── Sales ──────────────────────────────────────────────────────────
    'sales.max_discount_without_approval' => [
        'label'      => 'Max discount without supervisor approval (%)',
        'group'      => 'Sales',
        'type'       => 'percent',
        'default'    => 10,
        'rules'      => ['numeric', 'min:0', 'max:100'],
        'per_branch' => true,
        'help'       => 'Above this, the POS asks for a supervisor PIN.',
    ],
    'sales.quote_expiry_days' => [
        'label'      => 'Quotation validity (days)',
        'group'      => 'Sales',
        'type'       => 'int',
        'default'    => 30,
        'rules'      => ['integer', 'min:1', 'max:365'],
        'per_branch' => false,
        'help'       => 'Expired quotes cannot be converted.',
    ],
    'sales.layby_minimum_deposit_pct' => [
        'label'      => 'Lay-by minimum deposit (%)',
        'group'      => 'Sales',
        'type'       => 'percent',
        'default'    => 20,
        'rules'      => ['numeric', 'min:0', 'max:100'],
        'per_branch' => true,
        'help'       => 'Required before a lay-by is created.',
    ],
    'sales.return_window_days' => [
        'label'      => 'Standard return window (days)',
        'group'      => 'Sales',
        'type'       => 'int',
        'default'    => 30,
        'rules'      => ['integer', 'min:0', 'max:365'],
        'per_branch' => false,
        'help'       => 'Returns after this need manager approval.',
    ],
    'sales.loyalty_earn_rate' => [
        'label'      => 'Loyalty points per $1 spent',
        'group'      => 'Sales',
        'type'       => 'decimal',
        'default'    => 1,
        'rules'      => ['numeric', 'min:0'],
        'per_branch' => false,
        'help'       => 'Retail purchases only.',
    ],
    'sales.loyalty_expiry_months' => [
        'label'      => 'Loyalty points expiry (months)',
        'group'      => 'Sales',
        'type'       => 'int',
        'default'    => 12,
        'rules'      => ['integer', 'min:1', 'max:60'],
        'per_branch' => false,
        'help'       => 'Points lapse this long after being earned.',
    ],

    // ── Purchasing ─────────────────────────────────────────────────────
    'purchasing.po_approval_threshold' => [
        'label'      => 'PO value requiring second approval',
        'group'      => 'Purchasing',
        'type'       => 'money',
        'default'    => 5000,
        'rules'      => ['numeric', 'min:0'],
        'per_branch' => true,
        'sensitive'  => true,
        'help'       => 'Purchase orders above this need manager sign-off.',
    ],
    'purchasing.grn_over_receive_tolerance_pct' => [
        'label'      => 'GRN over-receive tolerance (%)',
        'group'      => 'Purchasing',
        'type'       => 'percent',
        'default'    => 10,
        'rules'      => ['numeric', 'min:0', 'max:100'],
        'per_branch' => false,
        'help'       => 'Receiving beyond PO qty by more than this needs a supervisor.',
    ],
    'purchasing.invoice_match_tolerance_pct' => [
        'label'      => 'Supplier invoice match tolerance (%)',
        'group'      => 'Purchasing',
        'type'       => 'percent',
        'default'    => 2,
        'rules'      => ['numeric', 'min:0', 'max:25'],
        'per_branch' => false,
        'help'       => '3-way match tolerance vs GRN value.',
    ],
    'purchasing.auto_requisition_on_reorder' => [
        'label'      => 'Auto-create requisitions at reorder point',
        'group'      => 'Purchasing',
        'type'       => 'bool',
        'default'    => true,
        'rules'      => ['boolean'],
        'per_branch' => true,
        'help'       => 'Draft requisitions raised automatically when stock hits reorder level.',
    ],

    // ── Customers / Credit ─────────────────────────────────────────────
    'credit.overdue_hold_days' => [
        'label'      => 'Auto-hold customers overdue beyond (days)',
        'group'      => 'Customers & Credit',
        'type'       => 'int',
        'default'    => 60,
        'rules'      => ['integer', 'min:0', 'max:365'],
        'per_branch' => false,
        'sensitive'  => true,
        'help'       => 'Credit sales blocked once any invoice is this overdue.',
    ],
    'credit.over_limit_hold_pct' => [
        'label'      => 'Auto-hold at % of credit limit',
        'group'      => 'Customers & Credit',
        'type'       => 'percent',
        'default'    => 110,
        'rules'      => ['numeric', 'min:100', 'max:200'],
        'per_branch' => false,
        'help'       => 'Balance above this % of limit places the account on hold.',
    ],
    'credit.statement_day' => [
        'label'      => 'Monthly statement day',
        'group'      => 'Customers & Credit',
        'type'       => 'int',
        'default'    => 1,
        'rules'      => ['integer', 'min:1', 'max:28'],
        'per_branch' => false,
        'help'       => 'Statements are generated and emailed on this day.',
    ],

    // ── Workshop ───────────────────────────────────────────────────────
    'workshop.promised_time_reminder_mins' => [
        'label'      => 'Promised-time reminder (minutes before)',
        'group'      => 'Workshop',
        'type'       => 'int',
        'default'    => 30,
        'rules'      => ['integer', 'min:5', 'max:240'],
        'per_branch' => true,
        'help'       => 'Technician and foreman are alerted this long before the promise.',
    ],
    'workshop.scope_growth_approval_pct' => [
        'label'      => 'Extra-work authorisation threshold (%)',
        'group'      => 'Workshop',
        'type'       => 'percent',
        'default'    => 10,
        'rules'      => ['numeric', 'min:0', 'max:100'],
        'per_branch' => false,
        'help'       => 'Job growth beyond this % of authorised value needs the customer.',
    ],
    'workshop.comeback_window_days' => [
        'label'      => 'Comeback window (days)',
        'group'      => 'Workshop',
        'type'       => 'int',
        'default'    => 30,
        'rules'      => ['integer', 'min:1', 'max:90'],
        'per_branch' => false,
        'help'       => 'Same vehicle, same fault within this window flags a comeback.',
    ],

    // ── Documents ──────────────────────────────────────────────────────
    'documents.invoice_footer_text' => [
        'label'      => 'Invoice footer text',
        'group'      => 'Documents',
        'type'       => 'string',
        'default'    => 'Thank you for your business. Goods remain our property until paid in full.',
        'rules'      => ['string', 'max:500'],
        'per_branch' => true,
        'help'       => 'Printed at the bottom of every tax invoice.',
    ],
    'documents.quote_terms_text' => [
        'label'      => 'Quotation terms text',
        'group'      => 'Documents',
        'type'       => 'string',
        'default'    => 'Prices are subject to stock availability and valid until the expiry date shown.',
        'rules'      => ['string', 'max:500'],
        'per_branch' => false,
        'help'       => 'Printed on every quotation.',
    ],
    'documents.receipt_copies' => [
        'label'      => 'Thermal receipt copies',
        'group'      => 'Documents',
        'type'       => 'int',
        'default'    => 1,
        'rules'      => ['integer', 'min:1', 'max:3'],
        'per_branch' => true,
        'help'       => 'Copies printed per POS sale.',
    ],

    // ── Notifications ──────────────────────────────────────────────────
    'notifications.low_stock_email' => [
        'label'      => 'Email buyers on low stock',
        'group'      => 'Notifications',
        'type'       => 'bool',
        'default'    => true,
        'rules'      => ['boolean'],
        'per_branch' => true,
        'help'       => 'Daily digest of parts at or below reorder point.',
    ],
    'notifications.sms_monthly_cap' => [
        'label'      => 'SMS monthly cap per branch',
        'group'      => 'Notifications',
        'type'       => 'int',
        'default'    => 500,
        'rules'      => ['integer', 'min:0'],
        'per_branch' => true,
        'help'       => 'Warning at 80%; customer SMS suspended at the cap.',
    ],

    // ── Security (Password Policy) ─────────────────────────────────────
    'security.password_min_length' => ['label' => 'Minimum password length', 'group' => 'Security', 'type' => 'int', 'default' => 8, 'rules' => ['integer', 'min:6', 'max:64'], 'per_branch' => false, 'help' => 'Shortest password a user may set.'],
    'security.password_require_mixed_case' => ['label' => 'Require upper & lower case', 'group' => 'Security', 'type' => 'bool', 'default' => true, 'rules' => ['boolean'], 'per_branch' => false, 'help' => 'Passwords must mix letter cases.'],
    'security.password_require_number' => ['label' => 'Require a number', 'group' => 'Security', 'type' => 'bool', 'default' => true, 'rules' => ['boolean'], 'per_branch' => false],
    'security.password_expiry_days' => ['label' => 'Password expiry (days, 0 = never)', 'group' => 'Security', 'type' => 'int', 'default' => 90, 'rules' => ['integer', 'min:0', 'max:365'], 'per_branch' => false],
    'security.max_failed_attempts' => ['label' => 'Max failed logins before lockout', 'group' => 'Security', 'type' => 'int', 'default' => 5, 'rules' => ['integer', 'min:3', 'max:20'], 'per_branch' => false],
    'security.lockout_minutes' => ['label' => 'Lockout duration (minutes)', 'group' => 'Security', 'type' => 'int', 'default' => 15, 'rules' => ['integer', 'min:1', 'max:1440'], 'per_branch' => false],

    // ── Communications (Email & SMS) ───────────────────────────────────
    'comms.email_from_name' => ['label' => 'Email "from" name', 'group' => 'Communications', 'type' => 'string', 'default' => 'SparesPro', 'rules' => ['string', 'max:100'], 'per_branch' => true],
    'comms.email_from_address' => ['label' => 'Email "from" address', 'group' => 'Communications', 'type' => 'string', 'default' => 'no-reply@sparespro.local', 'rules' => ['string', 'max:150'], 'per_branch' => true],
    'comms.sms_enabled' => ['label' => 'SMS notifications enabled', 'group' => 'Communications', 'type' => 'bool', 'default' => false, 'rules' => ['boolean'], 'per_branch' => true, 'help' => 'Requires an SMS gateway to be configured.'],
    'comms.sms_sender_id' => ['label' => 'SMS sender ID', 'group' => 'Communications', 'type' => 'string', 'default' => 'SPARES', 'rules' => ['string', 'max:11'], 'per_branch' => true],

    // ── Loyalty ─────────────────────────────────────────────────────────
    'loyalty.enabled' => ['label' => 'Loyalty programme enabled', 'group' => 'Sales', 'type' => 'bool', 'default' => true, 'rules' => ['boolean'], 'per_branch' => false],
];
