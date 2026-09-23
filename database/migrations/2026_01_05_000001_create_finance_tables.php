<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Accounts Receivable: customer receipts + allocations ──────────
        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->date('receipt_date');
            $table->string('method', 20); // cash | card | eft | bank_transfer
            $table->decimal('amount', 15, 2);
            $table->decimal('allocated', 15, 2)->default(0); // running sum of allocations
            $table->string('reference', 100)->nullable();
            $table->foreignId('gl_journal_id')->nullable()->constrained('gl_journals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
        });

        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('customer_receipts')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('sales_documents'); // the invoice
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['receipt_id', 'document_id']);
            $table->index('document_id');
        });

        // ── Accounts Payable: supplier payments + allocations ─────────────
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->date('payment_date');
            $table->string('method', 20); // eft | cash | cheque | bank_transfer
            $table->decimal('amount', 15, 2);
            $table->decimal('allocated', 15, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->string('batch_ref', 40)->nullable(); // payment-run grouping
            $table->foreignId('gl_journal_id')->nullable()->constrained('gl_journals')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('supplier_payments')->cascadeOnDelete();
            $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['payment_id', 'supplier_invoice_id']);
            $table->index('supplier_invoice_id');
        });

        // ── VAT returns ───────────────────────────────────────────────────
        Schema::create('vat_returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('output_vat', 15, 2)->default(0);
            $table->decimal('input_vat', 15, 2)->default(0);
            $table->decimal('net_payable', 15, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft | submitted | paid
            $table->foreignId('prepared_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_returns');
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('customer_receipts');
    }
};
