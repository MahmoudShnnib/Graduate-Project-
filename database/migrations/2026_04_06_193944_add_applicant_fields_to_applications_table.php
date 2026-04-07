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
        Schema::table('applications', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
            $table->string('email')->nullable()->after('name');
            $table->string('phone', 20)->nullable()->after('email');
            $table->foreignId('specialization_id')->nullable()->after('phone')->constrained()->nullOnDelete();
            $table->string('tracking_code')->nullable()->unique()->after('specialization_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['specialization_id']);
            $table->dropColumn(['name', 'email', 'phone', 'specialization_id', 'tracking_code']);
        });
    }
};
