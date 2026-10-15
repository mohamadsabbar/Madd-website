<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may use wa_reminder_unique (left prefix subscriber_id) for the subscriber FK,
        // so both FKs must be dropped before dropping that unique index.
        $this->dropForeignKeyIfExists('whatsapp_reminder_logs', 'subscriber_id');
        $this->dropForeignKeyIfExists('whatsapp_reminder_logs', 'invoice_id');

        if ($this->indexExists('whatsapp_reminder_logs', 'wa_reminder_unique')) {
            Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
                $table->dropUnique('wa_reminder_unique');
            });
        }

        Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->change();
        });

        if (! $this->indexExists('whatsapp_reminder_logs', 'wa_reminder_unique_v2')) {
            Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
                $table->unique(['subscriber_id', 'stage', 'period_key'], 'wa_reminder_unique_v2');
            });
        }

        Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $this->dropForeignKeyIfExists('whatsapp_reminder_logs', 'subscriber_id');
        $this->dropForeignKeyIfExists('whatsapp_reminder_logs', 'invoice_id');

        if ($this->indexExists('whatsapp_reminder_logs', 'wa_reminder_unique_v2')) {
            Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
                $table->dropUnique('wa_reminder_unique_v2');
            });
        }

        Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable(false)->change();
        });

        Schema::table('whatsapp_reminder_logs', function (Blueprint $table) {
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->cascadeOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->unique(['subscriber_id', 'invoice_id', 'stage', 'period_key'], 'wa_reminder_unique');
        });
    }

    private function dropForeignKeyIfExists(string $table, string $column): void
    {
        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$database, $table, $column]
        );

        if (! $row?->name) {
            return;
        }

        DB::statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$row->name.'`');
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS ok
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1',
            [$database, $table, $index]
        );

        return (bool) $row;
    }
};
