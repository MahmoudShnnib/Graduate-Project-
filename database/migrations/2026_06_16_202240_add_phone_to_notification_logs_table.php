<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            // Recipient number the notification was sent to (the phone for WhatsApp).
            // Nullable — older rows and any non-phone channel may have none.
            $table->string('phone', 30)->nullable()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
