<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_number', 20)->unique();
            $table->string('name', 255);
            $table->string('trading_name', 255)->nullable();
            $table->string('type', 20)->default('local'); // local | import | manufacturer | distributor | wholesaler
            $table->string('tax_number', 30)->nullable();
            $table->string('vat_number', 30)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->decimal('minimum_order_value', 15, 2)->default(0);
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_branch_code', 20)->nullable();
            $table->string('bank_account_name', 100)->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });

        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('position', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('supplier_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('name', 100);
            $table->date('effective_date')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending | active | superseded
            $table->foreignId('imported_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('unmatched_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('supplier_price_lists')->cascadeOnDelete();
            $table->unsignedBigInteger('part_id')->nullable(); // null = unmatched row
            $table->string('supplier_part_number', 100);
            $table->string('description', 255)->nullable();
            $table->decimal('cost_price', 15, 4);
            $table->decimal('minimum_qty', 10, 2)->default(1);
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('parts')->nullOnDelete();
            $table->index(['price_list_id', 'part_id']);
            $table->index('supplier_part_number');
        });

        Schema::create('approved_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->boolean('is_preferred')->default(false);
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['part_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approved_suppliers');
        Schema::dropIfExists('supplier_price_list_items');
        Schema::dropIfExists('supplier_price_lists');
        Schema::dropIfExists('supplier_contacts');
        Schema::dropIfExists('suppliers');
    }
};
