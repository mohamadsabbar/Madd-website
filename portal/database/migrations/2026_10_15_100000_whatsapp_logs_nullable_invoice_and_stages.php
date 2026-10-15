<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropUnique('wa_reminder_unique');
        });

        Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->change();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->unique(['subscriber_id', 'stage', 'period_key'], 'wa_reminder_unique_v2');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropUnique('wa_reminder_unique_v2');
        });

        Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable(false)->change();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->unique(['subscriber_id', 'invoice_id', 'stage', 'period_key'], 'wa_reminder_unique');
        });
    }
};
