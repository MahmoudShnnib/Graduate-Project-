<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->change();
            $table->string('status', 20)->default('sent')->change();
            $table->text('error_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropColumn('error_message');
            $table->foreignId('student_id')->nullable(false)->change();
            // Revert to a plain string, NOT ->enum(): on Postgres an enum column
            // creates a CHECK constraint that later value renames don't update,
            // which crashed prod once already. string(20) is data-equivalent here.
            $table->string('status', 20)->default('sent')->change();
        });
    }
};
