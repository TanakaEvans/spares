<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approved_suppliers', function (Blueprint $table) {
            // The supplier's own item code, preserved separately from our part_number
            // so their next listing can still be matched after a renumber.
            $table->string('supplier_part_number', 100)->nullable()->after('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('approved_suppliers', function (Blueprint $table) {
            $table->dropColumn('supplier_part_number');
        });
    }
};
