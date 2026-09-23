<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-edited overrides/additions; shipped guides live as markdown
        // files in resources/guides/. DB row wins over shipped file.
        Schema::create('page_guides', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique(); // route name
            $table->text('content');              // markdown
            $table->foreignId('updated_by')->nullable()->constrained('auth_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_guides');
    }
};
