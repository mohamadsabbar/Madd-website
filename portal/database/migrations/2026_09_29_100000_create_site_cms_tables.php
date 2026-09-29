<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->default('main');
            $table->json('payload');
            $table->timestamps();
        });

        Schema::create('site_leads', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32); // fiber | programming | business_join
            $table->string('status', 32)->default('new');
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->string('plan_id')->nullable();
            $table->string('plan_name')->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->string('source', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_leads');
        Schema::dropIfExists('site_configs');
    }
};
