<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriber_traffic_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ppp_username');
            $table->unsignedBigInteger('rx_bytes_total')->default(0);
            $table->unsignedBigInteger('tx_bytes_total')->default(0);
            $table->unsignedBigInteger('delta_rx')->nullable();
            $table->unsignedBigInteger('delta_tx')->nullable();
            $table->unsignedBigInteger('rx_rate_bps')->nullable()->comment('تقدير معدل التنزيل (بت/ث) بين اللقطتين');
            $table->unsignedBigInteger('tx_rate_bps')->nullable()->comment('تقدير معدل الرفع (بت/ث)');
            $table->timestamp('recorded_at');

            $table->index(['router_id', 'recorded_at']);
            $table->index(['subscriber_id', 'recorded_at']);
            $table->index(['ppp_username', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriber_traffic_snapshots');
    }
};
