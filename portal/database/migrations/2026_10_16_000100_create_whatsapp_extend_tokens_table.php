<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_extend_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 32);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['subscriber_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_extend_tokens');
    }
};
