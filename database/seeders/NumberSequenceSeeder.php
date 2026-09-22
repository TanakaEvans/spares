<?php

namespace Database\Seeders;

use App\Models\NumberSequence;
use Illuminate\Database\Seeder;

class NumberSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $sequences = [
            ['type' => 'invoice',            'prefix' => 'INV', 'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'quotation',          'prefix' => 'QT',  'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'sales_order',        'prefix' => 'SO',  'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'credit_note',        'prefix' => 'CN',  'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'delivery_note',      'prefix' => 'DN',  'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'receipt',            'prefix' => 'RCP', 'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'purchase_order',     'prefix' => 'PO',  'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'purchase_requisition', 'prefix' => 'PR', 'include_date' => true, 'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'grn',                'prefix' => 'GRN', 'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'supplier_return',    'prefix' => 'SR',  'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'job_card',           'prefix' => 'JC',  'include_date' => true,  'date_format' => 'Y',   'padding' => 5, 'reset_frequency' => 'yearly'],
            ['type' => 'stock_take',         'prefix' => 'ST',  'include_date' => true,  'date_format' => 'Y',   'padding' => 3, 'reset_frequency' => 'yearly'],
            ['type' => 'stock_adjustment',   'prefix' => 'ADJ', 'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'stock_transfer',     'prefix' => 'TRF', 'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'payment',            'prefix' => 'PMT', 'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'journal',            'prefix' => 'JNL', 'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'warranty_claim',     'prefix' => 'WC',  'include_date' => true,  'date_format' => 'Y',   'padding' => 4, 'reset_frequency' => 'yearly'],
            ['type' => 'layby',              'prefix' => 'LB',  'include_date' => true,  'date_format' => 'Ymd', 'padding' => 4, 'reset_frequency' => 'never'],
            ['type' => 'customer',           'prefix' => 'CUST', 'include_date' => false, 'date_format' => 'Ymd', 'padding' => 5, 'reset_frequency' => 'never'],
            ['type' => 'supplier',           'prefix' => 'SUPP', 'include_date' => false, 'date_format' => 'Ymd', 'padding' => 5, 'reset_frequency' => 'never'],
        ];

        foreach ($sequences as $sequence) {
            NumberSequence::updateOrCreate(
                ['type' => $sequence['type'], 'branch_id' => null],
                $sequence
            );
        }
    }
}
