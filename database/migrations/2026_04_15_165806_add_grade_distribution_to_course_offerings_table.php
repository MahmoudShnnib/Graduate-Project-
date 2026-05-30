<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_offerings', function (Blueprint $table) {
            // Defaults sum to 100. Final exam weighted heavier than coursework
            // per faculty norm (Note 2026-05-16: flipped from 60/40 to 40/60).
            $table->unsignedSmallInteger('coursework_max')->default(40)->after('is_open');
            $table->unsignedSmallInteger('final_max')->default(60)->after('coursework_max');
        });
    }

    public function down(): void
    {
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->dropColumn(['coursework_max', 'final_max']);
        });
    }
};
