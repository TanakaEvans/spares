<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = ['finance_student_ledgers', 'finance_cashbooks', 'finance_general_ledgers', 'finance_vendor_ledgers', 'finance_general_journals'];

file_put_contents('fk_log.txt', "Start Check CreatedBy\n");

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) continue;
    
    file_put_contents('fk_log.txt', "Checking $table...\n", FILE_APPEND);
    
    if (!Schema::hasColumn($table, 'created_by')) {
        file_put_contents('fk_log.txt', "  No created_by column\n", FILE_APPEND);
        continue;
    }
    
    $fks = DB::select("SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'created_by'", [$table]);
    
    if (count($fks) === 0) {
        file_put_contents('fk_log.txt', "  No FK on created_by\n", FILE_APPEND);
    }
    
    foreach ($fks as $fk) {
        file_put_contents('fk_log.txt', "  FK Name: " . $fk->CONSTRAINT_NAME . " Ref Table: " . $fk->REFERENCED_TABLE_NAME . "\n", FILE_APPEND);
    }
}
