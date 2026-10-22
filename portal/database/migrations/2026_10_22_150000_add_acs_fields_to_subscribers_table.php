<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('acs_serial', 100)->nullable()->after('pppoe_password');
            $table->string('acs_device_id', 191)->nullable()->after('acs_serial');
            $table->index('acs_serial');
            $table->unique('acs_device_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropUnique(['acs_device_id']);
            $table->dropIndex(['acs_serial']);
            $table->dropColumn(['acs_serial', 'acs_device_id']);
        });
    }
};
