<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admin-only `degree_image` field was consolidated into the admission
     * documents (`bachelor_certificate` / `remedial_statement`), so the column
     * is no longer collected or displayed anywhere.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('degree_image');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('degree_image')->nullable()->after('is_transfer');
        });
    }
};
