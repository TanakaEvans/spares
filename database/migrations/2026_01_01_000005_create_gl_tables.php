<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 20)->unique();
            $table->string('name', 255);
            $table->string('type', 20); // asset | liability | equity | revenue | expense
            $table->string('category', 50)->nullable();
            $table->boolean('is_control_account')->default(false);
            $table->string('control_type', 30)->nullable(); // debtors | creditors | inventory
            $table->string('normal_balance', 10); // debit | credit
            $table->boolean('allow_direct_posting')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('gl_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open'); // open | closed | locked
            $table->timestamps();
        });

        Schema::create('gl_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('year_id')->constrained('gl_years')->cascadeOnDelete();
            $table->unsignedTinyInteger('period_number');
            $table->string('name', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open'); // open | closed | locked
            $table->foreignId('closed_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['year_id', 'period_number']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('gl_journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_number', 30)->unique();
            $table->string('journal_type', 30); // sales | purchase | cash_receipt | bank | general | opening | adjustment
            $table->foreignId('period_id')->constrained('gl_periods');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->date('journal_date');
            $table->string('description', 255);
            $table->string('reference', 100)->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('status', 20)->default('posted'); // posted | reversed
            $table->foreignId('posted_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->foreignId('reversing_journal_id')->nullable()->constrained('gl_journals')->nullOnDelete();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('journal_date');
        });

        Schema::create('gl_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('gl_journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('gl_accounts');
            $table->string('description', 255)->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->timestamps();

            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gl_journal_lines');
        Schema::dropIfExists('gl_journals');
        Schema::dropIfExists('gl_periods');
        Schema::dropIfExists('gl_years');
        Schema::dropIfExists('gl_accounts');
    }
};
