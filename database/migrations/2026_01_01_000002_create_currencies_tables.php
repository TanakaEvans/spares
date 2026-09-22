<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name', 50);
            $table->string('symbol', 5);
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Rates are quoted against the base currency: 1 unit of base = rate units of this currency.
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->cascadeOnDelete();
            $table->date('rate_date');
            $table->decimal('buy_rate', 15, 6);
            $table->decimal('sell_rate', 15, 6);
            $table->string('source', 20)->default('manual');
            $table->foreignId('created_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['currency_id', 'rate_date']);
            $table->index('rate_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
