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
        Schema::table('course_surveys', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'semester_id', 'course_id']);
            $table->unique(['student_id', 'semester_id', 'course_id', 'is_retake']);
        });
    }

    public function down(): void
    {
        Schema::table('course_surveys', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'semester_id', 'course_id', 'is_retake']);
            $table->unique(['student_id', 'semester_id', 'course_id']);
        });
    }
};
