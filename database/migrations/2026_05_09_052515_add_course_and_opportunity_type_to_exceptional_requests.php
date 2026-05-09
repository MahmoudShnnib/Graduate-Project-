<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exceptional_requests', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->string('opportunity_type')->nullable()->after('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('exceptional_requests', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn(['course_id', 'opportunity_type']);
        });
    }
};
