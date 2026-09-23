<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('vat_number', 30)->nullable()->after('tax_number');
            $table->string('bank_name', 100)->nullable()->after('currency');
            $table->string('bank_branch_code', 20)->nullable()->after('bank_name');
            $table->string('bank_account_name', 100)->nullable()->after('bank_branch_code');
            $table->string('bank_account_number', 30)->nullable()->after('bank_account_name');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->string('logo', 255)->nullable()->after('city');
            $table->string('bank_name', 100)->nullable()->after('logo');
            $table->string('bank_branch_code', 20)->nullable()->after('bank_name');
            $table->string('bank_account_name', 100)->nullable()->after('bank_branch_code');
            $table->string('bank_account_number', 30)->nullable()->after('bank_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['vat_number', 'bank_name', 'bank_branch_code', 'bank_account_name', 'bank_account_number']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['logo', 'bank_name', 'bank_branch_code', 'bank_account_name', 'bank_account_number']);
        });
    }
};
