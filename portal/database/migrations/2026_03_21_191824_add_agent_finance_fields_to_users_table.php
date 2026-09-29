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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->default(10);
            }
            if (! Schema::hasColumn('users', 'monthly_collection_target')) {
                $table->decimal('monthly_collection_target', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('users', 'commission_paid_total')) {
                $table->decimal('commission_paid_total', 12, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['commission_percent', 'monthly_collection_target', 'commission_paid_total']);
        });
    }
};
