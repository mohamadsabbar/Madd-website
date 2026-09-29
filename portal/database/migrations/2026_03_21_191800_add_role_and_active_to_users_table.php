<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 32)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 32)->default('subscriber')->after('password');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_values(array_filter(
                ['phone', 'role', 'is_active'],
                fn (string $c) => Schema::hasColumn('users', $c),
            ));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
