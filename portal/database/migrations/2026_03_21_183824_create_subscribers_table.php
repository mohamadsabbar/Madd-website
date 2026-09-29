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
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone')->index();
            $table->string('address')->nullable();
            $table->string('pppoe_username')->unique();
            $table->string('pppoe_password');
            $table->foreignId('plan_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('router_id')->constrained()->cascadeOnUpdate();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->boolean('is_online')->default(false);
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
