<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `system_events` — append-only table for events that span the whole system
 * (not tied to any one entity). Used by the migration-mode cutover and any
 * future high-impact administrative event that should be permanently auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_events', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->char('public_id', 26)->unique();
            $t->string('type', 100);                        // e.g. 'migration_completed'
            $t->foreignId('actor_user_id')->nullable();     // who triggered it
            $t->text('reason')->nullable();
            $t->json('payload')->nullable();                // additional context
            $t->ipAddress('ip_address')->nullable();
            $t->timestamp('created_at')->useCurrent();

            $t->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_events');
    }
};
