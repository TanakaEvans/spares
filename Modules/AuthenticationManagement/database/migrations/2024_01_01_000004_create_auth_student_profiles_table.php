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
        Schema::create('auth_student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('auth_users')->onDelete('cascade');
            $table->string('student_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('class_level', 50)->nullable();
            $table->string('section', 50)->nullable();
            $table->date('enrollment_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred'])->default('active');
            $table->json('medical_conditions')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();
            
            $table->index(['student_number', 'status']);
            $table->index(['user_id']);
            $table->index(['class_level', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_student_profiles');
    }
};
