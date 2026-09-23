<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_makes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 10)->unique();
            $table->string('country_of_origin', 100)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('make_id')->constrained('vehicle_makes')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('body_type', 30)->nullable();
            $table->string('generation', 50)->nullable();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['make_id', 'name', 'generation']);
            $table->index('name');
        });

        Schema::create('engine_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('make_id')->nullable()->constrained('vehicle_makes')->nullOnDelete();
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('capacity_cc')->nullable();
            $table->string('fuel_type', 20)->nullable();
            $table->string('aspiration', 25)->nullable();
            $table->unsignedTinyInteger('cylinders')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('vehicle_models')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('engine_code', 30)->nullable();
            $table->unsignedInteger('engine_size_cc')->nullable();
            $table->string('fuel_type', 20)->nullable();
            $table->unsignedSmallInteger('power_kw')->nullable();
            $table->string('transmission', 20)->nullable();
            $table->string('drive', 10)->nullable();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->timestamps();

            $table->unique(['model_id', 'name']);
            $table->index('engine_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_variants');
        Schema::dropIfExists('engine_codes');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_makes');
    }
};
