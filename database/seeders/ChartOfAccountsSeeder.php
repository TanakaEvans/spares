<?php

namespace Database\Seeders;

use App\Models\GlAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // [code, name, type, category, control_type|null, normal_balance, allow_direct_posting]
        $accounts = [
            // ASSETS
            ['1110', 'Till / Petty Cash',            'asset',     'current_asset', null,        'debit',  true],
            ['1120', 'Bank Account - Main',          'asset',     'current_asset', null,        'debit',  true],
            ['1130', 'Bank Account - Savings',       'asset',     'current_asset', null,        'debit',  true],
            ['1210', 'Trade Debtors Control',        'asset',     'current_asset', 'debtors',   'debit',  false],
            ['1220', 'Allowance for Bad Debts',      'asset',     'current_asset', null,        'credit', true],
            ['1310', 'Inventory Asset',              'asset',     'current_asset', 'inventory', 'debit',  false],
            ['1320', 'Stock In Transit',             'asset',     'current_asset', null,        'debit',  true],
            ['1400', 'Prepayments',                  'asset',     'current_asset', null,        'debit',  true],
            ['1510', 'Property, Plant & Equipment',  'asset',     'non_current',   null,        'debit',  true],
            ['1520', 'Accumulated Depreciation',     'asset',     'non_current',   null,        'credit', true],
            ['1530', 'Motor Vehicles',               'asset',     'non_current',   null,        'debit',  true],

            // LIABILITIES
            ['2110', 'Trade Creditors Control',      'liability', 'current_liability', 'creditors', 'credit', false],
            ['2120', 'GRN Accruals (received not invoiced)', 'liability', 'current_liability', null, 'credit', false],
            ['2210', 'VAT Output',                   'liability', 'tax',           null,        'credit', false],
            ['2220', 'VAT Input',                    'liability', 'tax',           null,        'debit',  false],
            ['2230', 'VAT Control',                  'liability', 'tax',           null,        'credit', true],
            ['2300', 'Lay-by Deposits',              'liability', 'current_liability', null,    'credit', true],
            ['2400', 'Accrued Liabilities',          'liability', 'current_liability', null,    'credit', true],
            ['2510', 'Bank Loan',                    'liability', 'long_term',     null,        'credit', true],

            // EQUITY
            ['3100', 'Share Capital',                'equity',    'equity',        null,        'credit', true],
            ['3200', 'Retained Earnings',            'equity',    'equity',        null,        'credit', true],
            ['3300', 'Opening Balance Equity',       'equity',    'equity',        null,        'credit', true],

            // REVENUE
            ['4100', 'Sales - Parts (Retail)',       'revenue',   'sales',         null,        'credit', false],
            ['4200', 'Sales - Parts (Trade)',        'revenue',   'sales',         null,        'credit', false],
            ['4300', 'Sales - Labour (Workshop)',    'revenue',   'sales',         null,        'credit', false],
            ['4400', 'Sales - Oils & Lubricants',    'revenue',   'sales',         null,        'credit', false],
            ['4900', 'Sales Returns & Allowances',   'revenue',   'sales',         null,        'debit',  false],

            // COST OF GOODS SOLD
            ['5100', 'Cost of Parts Sold',           'expense',   'cogs',          null,        'debit',  false],
            ['5200', 'Cost of Labour (Internal)',    'expense',   'cogs',          null,        'debit',  true],
            ['5300', 'Stock Write-offs',             'expense',   'cogs',          null,        'debit',  false],
            ['5400', 'Warranty Recovery',            'expense',   'cogs',          null,        'credit', true],
            ['5500', 'FX Gain/Loss',                 'expense',   'cogs',          null,        'debit',  false],

            // OPERATING EXPENSES
            ['6000', 'Salaries & Wages',             'expense',   'operating',     null,        'debit',  true],
            ['6100', 'Rent & Occupancy',             'expense',   'operating',     null,        'debit',  true],
            ['6200', 'Motor Vehicle Expenses',       'expense',   'operating',     null,        'debit',  true],
            ['6300', 'Telephone & Internet',         'expense',   'operating',     null,        'debit',  true],
            ['6400', 'Advertising & Marketing',      'expense',   'operating',     null,        'debit',  true],
            ['6500', 'Bank Charges',                 'expense',   'operating',     null,        'debit',  true],
            ['6600', 'Depreciation',                 'expense',   'operating',     null,        'debit',  true],
            ['6700', 'Insurance',                    'expense',   'operating',     null,        'debit',  true],
            ['6800', 'Repairs & Maintenance',        'expense',   'operating',     null,        'debit',  true],
            ['6900', 'Stationery & Printing',        'expense',   'operating',     null,        'debit',  true],
            ['7000', 'Other Operating Expenses',     'expense',   'operating',     null,        'debit',  true],
        ];

        foreach ($accounts as [$code, $name, $type, $category, $controlType, $normalBalance, $allowDirect]) {
            GlAccount::updateOrCreate(['account_code' => $code], [
                'name' => $name,
                'type' => $type,
                'category' => $category,
                'is_control_account' => $controlType !== null,
                'control_type' => $controlType,
                'normal_balance' => $normalBalance,
                'allow_direct_posting' => $allowDirect,
                'is_active' => true,
            ]);
        }
    }
}
