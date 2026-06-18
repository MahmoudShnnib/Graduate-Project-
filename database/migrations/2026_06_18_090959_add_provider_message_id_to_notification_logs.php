<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qonvo's message id, kept so a 'pending' (queued) WhatsApp log can later be
     * reconciled against GET /whatsapp/messages/{id} for its real delivery status.
     */
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('provider_message_id')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropColumn('provider_message_id');
        });
    }
};
