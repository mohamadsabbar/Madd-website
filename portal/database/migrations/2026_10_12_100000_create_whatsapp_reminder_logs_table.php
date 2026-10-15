<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            /** before_3 | before_1 | after */
            $table->string('stage', 32);
            /**
             * Idempotency period:
             * - before_*: subscription end_date (Y-m-d)
             * - after: calendar day (Y-m-d)
             */
            $table->string('period_key', 32);
            $table->string('status', 32)->default('queued'); // queued|sent|failed|skipped
            $table->string('phone', 32)->nullable();
            $table->string('template_name', 120)->nullable();
            $table->string('provider_message_id', 191)->nullable();
            $table->string('error_summary', 500)->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();

            $table->unique(['subscriber_id', 'invoice_id', 'stage', 'period_key'], 'wa_reminder_unique');
            $table->index(['stage', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_reminder_logs');
    }
};
