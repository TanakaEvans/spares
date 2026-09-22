<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('prefix', 10);
            $table->boolean('include_date')->default(true);
            $table->string('date_format', 10)->default('Ymd');
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(4);
            $table->string('reset_frequency', 10)->default('never');
            $table->date('last_reset_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
