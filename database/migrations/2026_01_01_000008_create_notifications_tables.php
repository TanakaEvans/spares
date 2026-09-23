<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel's native per-user notification feed (the bell).
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // The routing matrix: event → recipients × channel (10.13).
        Schema::create('notification_routes', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 100);
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('channel', 10); // bell | email | sms
            $table->string('recipient_type', 20); // role | user
            $table->unsignedBigInteger('recipient_id');
            $table->string('digest', 10)->default('instant'); // instant | daily
            $table->timestamps();

            $table->unique(
                ['event_key', 'branch_id', 'channel', 'recipient_type', 'recipient_id'],
                'notification_routes_unique'
            );
            $table->index('event_key');
        });

        // Email/SMS delivery log with retry visibility (10.13).
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 100);
            $table->string('channel', 10);
            $table->string('recipient', 255); // email address / phone / user id
            $table->string('subject', 255)->nullable();
            $table->string('status', 20)->default('queued'); // queued | sent | failed
            $table->text('error')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_routes');
        Schema::dropIfExists('notifications');
    }
};
