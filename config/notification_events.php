<?php

/*
|--------------------------------------------------------------------------
| Notifications Centre — event catalogue (10.13)
|--------------------------------------------------------------------------
| Events are DECLARED here (typed, like settings); routing/recipients are
| data in notification_routes. Modules append their events as they land.
| mandatory: recipients cannot mute it. sms_capable: SMS channel allowed.
*/

return [
    'system.backup_failed' => [
        'label' => 'Backup failed',
        'group' => 'System',
        'severity' => 'critical',
        'mandatory' => true,
        'sms_capable' => true,
    ],
    'system.health_check_red' => [
        'label' => 'Health check turned red',
        'group' => 'System',
        'severity' => 'critical',
        'mandatory' => true,
        'sms_capable' => false,
    ],
    'currency.daily_rate_missing' => [
        'label' => 'Exchange rate not captured today',
        'group' => 'Currency',
        'severity' => 'warning',
        'mandatory' => false,
        'sms_capable' => false,
    ],
    'inventory.stock_below_reorder' => [
        'label' => 'Stock below reorder point',
        'group' => 'Inventory',
        'severity' => 'warning',
        'mandatory' => false,
        'sms_capable' => false,
    ],
    'purchasing.po_overdue' => [
        'label' => 'Purchase order past expected date',
        'group' => 'Purchasing',
        'severity' => 'warning',
        'mandatory' => false,
        'sms_capable' => false,
    ],
    'sales.back_order_received' => [
        'label' => 'Back-ordered part received',
        'group' => 'Sales',
        'severity' => 'info',
        'mandatory' => false,
        'sms_capable' => false,
    ],
    'customers.credit_hold_applied' => [
        'label' => 'Customer placed on credit hold',
        'group' => 'Customers',
        'severity' => 'warning',
        'mandatory' => true,
        'sms_capable' => false,
    ],
    'workshop.job_promised_time_near' => [
        'label' => 'Job promised time approaching',
        'group' => 'Workshop',
        'severity' => 'warning',
        'mandatory' => false,
        'sms_capable' => false,
    ],
    'workshop.job_completed' => [
        'label' => 'Job card completed (customer notification)',
        'group' => 'Workshop',
        'severity' => 'info',
        'mandatory' => false,
        'sms_capable' => true,
    ],
];
