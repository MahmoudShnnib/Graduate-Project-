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
        Schema::create('telegram_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('purpose')->default('apply_phone');
            $table->string('intended_phone');
            $table->string('chat_id')->nullable();
            $table->string('telegram_phone')->nullable();
            $table->string('telegram_user_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['intended_phone', 'purpose']);
            $table->index('chat_id');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_verifications');
    }
};
