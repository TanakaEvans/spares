<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rule 10: every override / approval / master-data change is audited —
        // who, when, before/after. Append-only; nothing in the app updates or
        // deletes rows here.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('action', 60);                 // e.g. customer.updated, sales.discount_override
            $table->string('auditable_type', 120)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('label', 150)->nullable();     // human handle of the record (part number, customer name…)
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('note', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // The approver of an over-limit discount is stored on the invoice itself.
        Schema::table('sales_documents', function (Blueprint $table) {
            $table->foreignId('discount_approved_by')->nullable()->after('salesperson_id')->constrained('auth_users')->nullOnDelete();
        });

        // Scheduled / on-demand System Health runs persist so the screen shows history.
        Schema::create('health_check_runs', function (Blueprint $table) {
            $table->id();
            $table->string('trigger', 20);                // scheduled | manual
            $table->string('overall', 10);                // ok | warn | fail
            $table->unsignedSmallInteger('ok_count')->default(0);
            $table->unsignedSmallInteger('warn_count')->default(0);
            $table->unsignedSmallInteger('fail_count')->default(0);
            $table->json('results');
            $table->timestamp('ran_at');
            $table->timestamps();

            $table->index('ran_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_check_runs');
        Schema::table('sales_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_approved_by');
        });
        Schema::dropIfExists('audit_logs');
    }
};
