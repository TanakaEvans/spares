<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('type', 20)->default('custom'); // retail | trade | wholesale | custom
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts')->cascadeOnDelete();
            $table->decimal('price', 15, 2); // VAT-exclusive
            $table->timestamps();

            $table->unique(['price_list_id', 'part_id']);
        });

        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 20)->unique();
            $table->string('type', 20)->default('individual'); // individual | business | fleet | dealer | cash
            $table->string('name', 255);
            $table->string('trading_name', 255)->nullable();
            $table->string('vat_number', 30)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();
            $table->unsignedSmallInteger('payment_terms_days')->default(0); // 0 = COD/no account
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->boolean('on_hold')->default(false);
            $table->string('hold_reason', 255)->nullable();
            $table->boolean('is_walk_in')->default(false); // the seeded Cash Customer
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('phone');
        });

        Schema::create('sales_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 30)->unique();
            $table->string('document_type', 20); // quotation | order | invoice | credit_note
            $table->string('status', 20); // quotes: open/converted/expired/cancelled · orders: confirmed/invoiced/cancelled · invoice/credit_note: posted
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('salesperson_id')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->date('document_date');
            $table->date('expiry_date')->nullable(); // quotes
            $table->foreignId('parent_id')->nullable()->constrained('sales_documents')->nullOnDelete();
            $table->decimal('subtotal_excl', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total_incl', 15, 2)->default(0);
            $table->foreignId('gl_journal_id')->nullable()->constrained('gl_journals')->nullOnDelete();
            $table->string('credit_mode', 20)->nullable(); // credit notes: refund_cash | account
            $table->string('reason', 255)->nullable(); // credit notes
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'status']);
            $table->index('customer_id');
        });

        Schema::create('sales_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('sales_documents')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->string('description', 255);
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_price', 15, 2); // VAT-exclusive, after list resolution
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('line_total_excl', 15, 2);
            $table->decimal('line_total_incl', 15, 2);
            $table->decimal('unit_cost', 15, 4)->default(0); // AVCO captured at posting (COGS)
            $table->decimal('qty_credited', 10, 2)->default(0); // invoices: running credit-note cap
            $table->foreignId('source_line_id')->nullable()->constrained('sales_document_lines')->nullOnDelete();
            $table->timestamps();

            $table->index('part_id');
        });

        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('sales_documents')->cascadeOnDelete();
            $table->string('method', 20); // cash | card | eft | account
            $table->decimal('amount', 15, 2);
            $table->decimal('tendered', 15, 2)->nullable(); // cash
            $table->decimal('change_given', 15, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_takes', function (Blueprint $table) {
            $table->id();
            $table->string('take_number', 30)->unique();
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('type', 10)->default('full'); // full | spot
            $table->string('status', 20)->default('counting'); // counting | review | posted | cancelled
            $table->foreignId('started_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->foreignId('adjustment_id')->nullable()->constrained('stock_adjustments')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_take_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('take_id')->constrained('stock_takes')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->decimal('system_qty', 10, 2); // frozen snapshot
            $table->decimal('counted_qty', 10, 2)->nullable();
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['take_id', 'part_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_take_lines');
        Schema::dropIfExists('stock_takes');
        Schema::dropIfExists('sales_payments');
        Schema::dropIfExists('sales_document_lines');
        Schema::dropIfExists('sales_documents');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('customer_groups');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
    }
};
