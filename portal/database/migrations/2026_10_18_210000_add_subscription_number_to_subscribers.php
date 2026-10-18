<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->string('subscription_number', 32)->nullable()->unique()->after('id');
        });

        $rows = DB::table('subscribers')->select('id')->orderBy('id')->get();
        foreach ($rows as $row) {
            DB::table('subscribers')->where('id', $row->id)->update([
                'subscription_number' => 'MADD-'.str_pad((string) $row->id, 6, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropUnique(['subscription_number']);
            $table->dropColumn('subscription_number');
        });
    }
};
