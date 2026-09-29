<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_ppp_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('router_id')->constrained()->cascadeOnDelete();
            $table->string('mikrotik_internal_id')->nullable()->comment('.id من الراوتر');
            $table->string('ppp_name');
            $table->string('profile')->nullable();
            $table->string('service')->nullable();
            $table->boolean('disabled')->default(false);
            $table->text('comment')->nullable();
            $table->foreignId('subscriber_id')->nullable()->constrained('subscribers')->nullOnDelete();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['router_id', 'ppp_name']);
            $table->index('profile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_ppp_secrets');
    }
};
