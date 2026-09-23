<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('buyer_id')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->string('status', 20)->default('draft'); // draft|submitted|confirmed|partial|received|closed|cancelled
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('supplier_ref', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->string('supplier_part_number', 100)->nullable();
            $table->string('description', 255);
            $table->decimal('qty_ordered', 10, 2);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('line_total', 15, 2);
            $table->decimal('qty_received', 10, 2)->default(0);
            $table->timestamps();

            $table->index('part_id');
        });

        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 30)->unique();
            $table->foreignId('po_id')->constrained('purchase_orders');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('received_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->date('received_date');
            $table->string('status', 20)->default('draft'); // draft | posted
            $table->string('delivery_note_number', 100)->nullable();
            $table->foreignId('gl_journal_id')->nullable()->constrained('gl_journals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('grn_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->cascadeOnDelete();
            $table->foreignId('po_line_id')->constrained('purchase_order_lines');
            $table->foreignId('part_id')->constrained('parts');
            $table->decimal('qty_received', 10, 2);
            $table->decimal('qty_rejected', 10, 2)->default(0);
            $table->string('rejection_reason', 255)->nullable();
            $table->decimal('unit_cost', 15, 4);
            $table->foreignId('bin_location_id')->nullable()->constrained('bin_locations')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique(); // our reference
            $table->string('supplier_ref', 100); // supplier's invoice number
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('grn_id')->constrained('goods_received_notes');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->string('status', 20)->default('draft'); // draft | matched | disputed | posted
            $table->string('dispute_reason', 255)->nullable();
            $table->foreignId('gl_journal_id')->nullable()->constrained('gl_journals')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('reason', 30); // damaged | incorrect_part | excess_stock | warranty
            $table->string('status', 20)->default('draft'); // draft | approved | shipped | credited
            $table->string('supplier_rma', 100)->nullable();
            $table->string('credit_note_ref', 100)->nullable();
            $table->decimal('credit_total', 15, 2)->nullable();
            $table->foreignId('gl_journal_id')->nullable()->constrained('gl_journals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('supplier_returns')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_cost', 15, 4);
            $table->string('condition', 20)->default('damaged'); // new | damaged | used
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 30)->unique();
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('reason_code', 30); // damage|write_off|correction|found|theft|expiry
            $table->string('status', 20)->default('draft'); // draft | posted
            $table->foreignId('created_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->foreignId('gl_journal_id')->nullable()->constrained('gl_journals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->string('direction', 5); // in | out
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_cost', 15, 4)->nullable(); // required for IN
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('from_branch_id')->constrained('branches');
            $table->foreignId('to_branch_id')->constrained('branches');
            $table->string('status', 20)->default('draft'); // draft | dispatched | received | cancelled
            $table->foreignId('created_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_cost', 15, 4)->nullable(); // captured at dispatch (AVCO)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('supplier_return_lines');
        Schema::dropIfExists('supplier_returns');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('grn_lines');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
