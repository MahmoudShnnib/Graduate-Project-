<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->unsignedSmallInteger('coursework_max')->default(60)->after('is_open');
            $table->unsignedSmallInteger('final_max')->default(40)->after('coursework_max');
        });
    }

    public function down(): void
    {
        Schema::table('course_offerings', function (Blueprint $table) {
            $table->dropColumn(['coursework_max', 'final_max']);
        });
    }
};
