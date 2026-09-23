<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales lines gain a type so a workshop invoice can carry labour lines
        // (no part, revenue to 4300). Existing rows default to 'part'.
        Schema::table('sales_document_lines', function (Blueprint $table) {
            $table->string('line_type', 12)->default('part')->after('part_id'); // part | labour
            $table->string('labour_code', 30)->nullable()->after('description');
        });
        Schema::table('sales_document_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('part_id')->nullable()->change();
        });

        Schema::create('customer_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('make_id')->nullable()->constrained('vehicle_makes')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('vehicle_variants')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('registration', 20);
            $table->string('vin', 17)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('engine_code', 30)->nullable();
            $table->string('colour', 40)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('registration');
            $table->index('customer_id');
        });

        Schema::create('vehicle_service_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('customer_vehicles')->cascadeOnDelete();
            $table->foreignId('job_card_id')->nullable();
            $table->date('service_date');
            $table->unsignedInteger('odometer')->nullable();
            $table->string('summary', 255);
            $table->timestamps();

            $table->index('vehicle_id');
        });

        Schema::create('labour_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('description', 255);
            $table->string('category', 60)->nullable();
            $table->string('rate_type', 20)->default('flat_rate'); // flat_rate | actual_time | fixed_price
            $table->decimal('standard_hours', 6, 2)->default(0);
            $table->decimal('default_rate', 15, 2)->default(0); // per hour, or fixed price
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('labour_code_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_code_id')->constrained('labour_codes')->cascadeOnDelete();
            $table->foreignId('make_id')->nullable()->constrained('vehicle_makes')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('vehicle_models')->nullOnDelete();
            $table->decimal('flat_rate', 15, 2); // overrides default for this make/model
            $table->timestamps();

            $table->index('labour_code_id');
        });

        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name', 255);
            $table->string('skill_level', 20)->default('qualified'); // apprentice | qualified | master
            $table->string('specialisations', 255)->nullable();
            $table->decimal('cost_rate', 15, 2)->default(0); // internal cost per hour (for costing)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->string('job_number', 30)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->string('status', 20)->default('open'); // open→allocated→in_progress→(awaiting_parts/awaiting_customer/on_hold)→quality_check→completed→invoiced→closed | cancelled
            $table->text('reported_fault')->nullable();
            $table->text('work_done')->nullable();
            $table->unsignedInteger('odometer_in')->nullable();
            $table->unsignedInteger('odometer_out')->nullable();
            $table->boolean('authorisation_required')->default(false);
            $table->timestamp('promised_at')->nullable();
            $table->foreignId('sales_document_id')->nullable()->constrained('sales_documents')->nullOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('invoiced_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'branch_id']);
        });

        Schema::create('job_card_labours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('labour_code_id')->nullable()->constrained('labour_codes')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('hours', 6, 2)->default(0);
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0); // technician cost_rate × hours (job costing)
            $table->boolean('is_warranty')->default(false);
            $table->timestamps();
        });

        Schema::create('job_card_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->string('description', 255);
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_price', 15, 2)->default(0); // billed
            $table->decimal('unit_cost', 15, 4)->default(0);  // AVCO captured at issue
            $table->string('status', 12)->default('requested'); // requested | issued | returned
            $table->boolean('is_warranty')->default(false);
            $table->foreignId('issued_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('technician_time_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('technicians');
            $table->timestamp('clock_on');
            $table->timestamp('clock_off')->nullable();
            $table->unsignedInteger('minutes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_time_logs');
        Schema::dropIfExists('job_card_parts');
        Schema::dropIfExists('job_card_labours');
        Schema::dropIfExists('job_cards');
        Schema::dropIfExists('technicians');
        Schema::dropIfExists('labour_code_rates');
        Schema::dropIfExists('labour_codes');
        Schema::dropIfExists('vehicle_service_history');
        Schema::dropIfExists('customer_vehicles');
        Schema::table('sales_document_lines', function (Blueprint $table) {
            $table->dropColumn(['line_type', 'labour_code']);
        });
    }
};
