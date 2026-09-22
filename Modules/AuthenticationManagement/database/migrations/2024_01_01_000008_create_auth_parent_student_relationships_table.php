<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('auth_parent_student_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('auth_parent_profiles')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('auth_student_profiles')->onDelete('cascade');
            $table->enum('relationship_type', ['father', 'mother', 'guardian', 'other'])->default('father');
            $table->boolean('is_primary_contact')->default(false);
            $table->boolean('has_pickup_permission')->default(true);
            $table->boolean('receives_notifications')->default(true);
            $table->timestamps();
            
            $table->unique(['parent_id', 'student_id']);
            $table->index(['student_id', 'is_primary_contact']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_parent_student_relationships');
    }
};
