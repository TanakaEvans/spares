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
        Schema::create('auth_staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('auth_users')->onDelete('cascade');
            $table->string('employee_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone_number', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position', 150)->nullable();
            $table->string('job_title', 150)->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('full_time');
            $table->decimal('salary', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');
            $table->json('responsibilities')->nullable();
            $table->string('supervisor_id', 50)->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();
            
            $table->index(['employee_number', 'status']);
            $table->index(['user_id']);
            $table->index(['department', 'status']);
            $table->index(['employment_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_staff_profiles');
    }
};
