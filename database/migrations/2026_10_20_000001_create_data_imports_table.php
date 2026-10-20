<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit log of every data upload: who, what, how many rows landed, and the row errors.
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 40);
            $table->string('file_name', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
            $table->index(['entity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
