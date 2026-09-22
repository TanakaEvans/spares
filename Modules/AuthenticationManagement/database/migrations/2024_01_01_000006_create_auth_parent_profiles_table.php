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
        Schema::create('auth_parent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('auth_users')->onDelete('cascade');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('phone_number', 20)->nullable();
            $table->string('work_phone', 20)->nullable();
            $table->text('home_address')->nullable();
            $table->text('work_address')->nullable();
            $table->string('occupation', 150)->nullable();
            $table->string('employer', 150)->nullable();
            $table->enum('relationship_type', ['father', 'mother', 'guardian', 'other'])->default('father');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('photo_url')->nullable();
            $table->timestamps();
            
            $table->index(['user_id']);
            $table->index(['relationship_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_parent_profiles');
    }
};
