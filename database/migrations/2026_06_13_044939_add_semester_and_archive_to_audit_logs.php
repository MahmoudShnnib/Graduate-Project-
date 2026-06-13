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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('semester_id')->nullable()->after('batch_id')->index();
        });

        // Cold storage: when a semester is archived its logs move here, keeping the live
        // audit_logs table lean while preserving the full trail.
        Schema::create('audit_log_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_id')->nullable();
            $table->uuid('batch_id')->nullable();
            $table->unsignedBigInteger('semester_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('archived_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log_archives');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('semester_id');
        });
    }
};
