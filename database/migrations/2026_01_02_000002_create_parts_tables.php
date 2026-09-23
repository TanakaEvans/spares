<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('part_categories')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('part_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 20)->unique();
            $table->string('country_of_origin', 100)->nullable();
            $table->boolean('is_oem_brand')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('abbreviation', 10)->unique();
            $table->timestamps();
        });

        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number', 50)->unique();
            $table->string('oem_number', 100)->nullable();
            $table->string('description', 255);
            $table->string('short_description', 100)->nullable();
            $table->foreignId('category_id')->constrained('part_categories');
            $table->foreignId('brand_id')->nullable()->constrained('part_brands')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units_of_measure');
            $table->string('barcode_ean', 20)->nullable()->unique();
            $table->string('barcode_code128', 50)->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->boolean('is_oem')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_discontinued')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('oem_number');
            $table->index(['category_id', 'is_active']);
            $table->index('description');
        });

        Schema::create('part_cross_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->cascadeOnDelete();
            $table->string('reference_number', 100);
            $table->foreignId('brand_id')->nullable()->constrained('part_brands')->nullOnDelete();
            $table->string('type', 20)->default('aftermarket'); // oem | aftermarket | competitor | ean
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('reference_number');
            $table->unique(['part_id', 'reference_number']);
        });

        Schema::create('part_supersessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('old_part_id')->constrained('parts')->cascadeOnDelete();
            $table->foreignId('new_part_id')->constrained('parts')->cascadeOnDelete();
            $table->date('effective_date')->nullable();
            $table->string('reason', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['old_part_id', 'new_part_id']);
        });

        Schema::create('part_fitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_id')->constrained('parts')->cascadeOnDelete();
            $table->foreignId('make_id')->constrained('vehicle_makes')->cascadeOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('vehicle_models')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('vehicle_variants')->cascadeOnDelete();
            $table->unsignedSmallInteger('year_from')->nullable();
            $table->unsignedSmallInteger('year_to')->nullable();
            $table->string('engine_code', 30)->nullable();
            $table->string('notes', 255)->nullable();
            $table->string('source', 20)->default('manual'); // manual | supplier | brand_guide | migration
            $table->boolean('confirmed')->default(true);
            $table->timestamps();

            $table->index(['make_id', 'model_id', 'variant_id']);
            $table->index('part_id');
        });

        Schema::create('bin_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('notes', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
        });

        // Primary bin per part+branch (multi-bin assignments land with stock takes).
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->foreignId('bin_location_id')->nullable()
                ->after('branch_id')->constrained('bin_locations')->nullOnDelete();
        });

        // Retrofit the deferred FKs now that parts exists.
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
        });
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->foreign('part_id')->references('id')->on('parts');
        });
    }

    public function down(): void
    {
        Schema::table('stock_ledger', fn (Blueprint $t) => $t->dropForeign(['part_id']));
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropForeign(['part_id']);
            $table->dropConstrainedForeignId('bin_location_id');
        });
        Schema::dropIfExists('bin_locations');
        Schema::dropIfExists('part_fitments');
        Schema::dropIfExists('part_supersessions');
        Schema::dropIfExists('part_cross_references');
        Schema::dropIfExists('parts');
        Schema::dropIfExists('units_of_measure');
        Schema::dropIfExists('part_brands');
        Schema::dropIfExists('part_categories');
    }
};
