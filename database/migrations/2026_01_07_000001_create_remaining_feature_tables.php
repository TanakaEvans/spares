<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customers › Communication Log
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->string('channel', 20)->default('note'); // call | email | visit | note
            $table->string('subject', 200);
            $table->text('body')->nullable();
            $table->timestamps();
            $table->index('customer_id');
        });

        // Customers › Loyalty Programme
        Schema::create('loyalty_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('sales_documents')->nullOnDelete();
            $table->integer('points'); // + earn, − redeem
            $table->string('reason', 120);
            $table->timestamps();
            $table->index('customer_id');
        });

        // Vehicle Reference › Technical Bulletins
        Schema::create('technical_bulletins', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->foreignId('make_id')->nullable()->constrained('vehicle_makes')->nullOnDelete();
            $table->string('category', 30)->default('service_note'); // fitment_warning | service_note | recall
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Sales › Promotions & Discounts
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('type', 20)->default('percent'); // percent | fixed
            $table->decimal('value', 10, 2);
            $table->string('applies_to', 20)->default('all'); // all | category | brand
            $table->foreignId('category_id')->nullable()->constrained('part_categories')->nullOnDelete();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Sales › Lay-by Management
        Schema::create('laybys', function (Blueprint $table) {
            $table->id();
            $table->string('layby_number', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->decimal('total', 15, 2);
            $table->decimal('deposit_paid', 15, 2)->default(0);
            $table->string('status', 20)->default('active'); // active | completed | cancelled | defaulted
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('layby_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layby_id')->constrained('laybys')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 20)->default('cash');
            $table->date('paid_on');
            $table->timestamps();
        });

        // Sales › Delivery Notes
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_number', 30)->unique();
            $table->foreignId('document_id')->nullable()->constrained('sales_documents')->nullOnDelete(); // source order/invoice
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('branch_id')->constrained('branches');
            $table->date('delivery_date');
            $table->string('driver', 120)->nullable();
            $table->string('vehicle_reg', 20)->nullable();
            $table->string('status', 20)->default('draft'); // draft | dispatched | delivered
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // Purchasing › Import Management
        Schema::create('import_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_ref', 40)->unique();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('origin_country', 60)->nullable();
            $table->string('status', 20)->default('ordered'); // ordered | in_transit | customs | cleared | received
            $table->date('eta')->nullable();
            $table->decimal('goods_value', 15, 2)->default(0);
            $table->decimal('freight', 15, 2)->default(0);
            $table->decimal('duty', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Inventory › Serial & Batch Tracking
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('serial', 80);
            $table->string('batch', 60)->nullable();
            $table->string('status', 20)->default('in_stock'); // in_stock | sold | returned
            $table->string('reference', 60)->nullable();
            $table->timestamps();
            $table->index('part_id');
            $table->index('serial');
        });

        // Finance › Cash & Bank Management
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('gl_account_id')->nullable()->constrained('gl_accounts')->nullOnDelete();
            $table->string('account_number', 40)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('txn_date');
            $table->string('description', 200);
            $table->decimal('amount', 15, 2); // + credit, − debit
            $table->boolean('reconciled')->default(false);
            $table->timestamps();
        });

        // Reports › Scheduled Reports
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('report_key', 60); // e.g. sales_summary, ar_ageing
            $table->string('frequency', 20)->default('monthly'); // daily | weekly | monthly
            $table->string('run_time', 5)->default('08:00');
            $table->string('recipients', 500)->nullable();
            $table->string('format', 10)->default('pdf');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['scheduled_reports', 'bank_statement_lines', 'bank_accounts', 'serial_numbers',
            'import_shipments', 'delivery_notes', 'layby_payments', 'laybys', 'promotions',
            'technical_bulletins', 'loyalty_entries', 'customer_notes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
