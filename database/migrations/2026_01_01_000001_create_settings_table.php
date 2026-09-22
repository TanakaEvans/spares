<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('system_settings');

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'branch_id']);
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
