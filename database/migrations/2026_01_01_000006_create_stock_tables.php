<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // part_id is unconstrained until the parts table lands in Phase 1;
        // the FK is added by that phase's migration.
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_id');
            $table->foreignId('branch_id')->constrained('branches');
            $table->decimal('qty_on_hand', 10, 2)->default(0);
            $table->decimal('qty_reserved', 10, 2)->default(0);
            $table->decimal('qty_on_order', 10, 2)->default(0);
            $table->decimal('qty_in_transit', 10, 2)->default(0);
            $table->decimal('average_cost', 15, 4)->default(0);
            $table->decimal('reorder_point', 10, 2)->default(0);
            $table->decimal('reorder_qty', 10, 2)->default(0);
            $table->decimal('max_level', 10, 2)->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            $table->unique(['part_id', 'branch_id']);
        });

        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_id');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('transaction_type', 30);
            $table->decimal('qty', 10, 2); // positive = IN, negative = OUT
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('running_balance', 10, 2);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['part_id', 'branch_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('transaction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('stock_levels');
    }
};
