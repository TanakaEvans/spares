<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$entries = App\Models\FinanceCashbook::where('reference_number', 'FGH-20260305-1-0001')->get();
foreach ($entries as $entry) {
    if ($entry->status === 'voided') {
        echo "Found original voided entry: {$entry->id}\n";
    }
}

$reversals = App\Models\FinanceCashbook::where('reference_number', 'LIKE', 'VOID-FGH-20260305-1-0001%')->get();
foreach ($reversals as $r) {
    echo "Found reversal entry: {$r->id} | Ref: {$r->reference_number} | Type: {$r->transaction_type}\n";
}
