<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for hot query paths.
 *
 * Production runs PostgreSQL, where foreign-key columns get NO automatic index
 * (unlike MySQL). These cover the columns this app filters/joins/sorts on but
 * that no existing unique/composite index serves — chiefly the status-as-pivot
 * lookups, single-active-semester queries, and per-record audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tier 1 — high-row, hot path
        Schema::table('student_enrollments', function (Blueprint $table): void {
            // Roster + per-offering enrollment counts; the (student_id, course_offering_id)
            // unique leads with student_id, so course_offering_id lookups are uncovered.
            $table->index(['course_offering_id', 'status'], 'student_enrollments_offering_status_index');
        });

        Schema::table('course_offerings', function (Blueprint $table): void {
            // "Current active-term offerings"; semester_id is the 2nd col of the unique.
            $table->index('semester_id', 'course_offerings_semester_id_index');
            // MyCourseResource scopes by faculty_id.
            $table->index('faculty_id', 'course_offerings_faculty_id_index');
        });

        Schema::table('grades', function (Blueprint $table): void {
            $table->index('status', 'grades_status_index');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->index('status', 'students_status_index');
            $table->index('specialization_id', 'students_specialization_id_index');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            // Per-record audit trail (polymorphic); table grows unbounded.
            $table->index(['model_type', 'model_id'], 'audit_logs_model_index');
        });

        // Tier 2 — request/log tables (status-filtered lists, student-scoped lookups)
        Schema::table('applications', function (Blueprint $table): void {
            $table->index('status', 'applications_status_index');
            $table->index('specialization_id', 'applications_specialization_id_index');
        });

        Schema::table('add_drop_requests', function (Blueprint $table): void {
            $table->index('student_id', 'add_drop_requests_student_id_index');
        });

        Schema::table('suspension_requests', function (Blueprint $table): void {
            $table->index('student_id', 'suspension_requests_student_id_index');
        });

        Schema::table('discussion_requests', function (Blueprint $table): void {
            $table->index('student_id', 'discussion_requests_student_id_index');
        });

        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->index('student_id', 'notification_logs_student_id_index');
        });

        Schema::table('course_surveys', function (Blueprint $table): void {
            // Survey-by-term; semester_id is not the leading column of the unique.
            $table->index('semester_id', 'course_surveys_semester_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropIndex('student_enrollments_offering_status_index');
        });

        Schema::table('course_offerings', function (Blueprint $table): void {
            $table->dropIndex('course_offerings_semester_id_index');
            $table->dropIndex('course_offerings_faculty_id_index');
        });

        Schema::table('grades', function (Blueprint $table): void {
            $table->dropIndex('grades_status_index');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropIndex('students_status_index');
            $table->dropIndex('students_specialization_id_index');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_model_index');
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex('applications_status_index');
            $table->dropIndex('applications_specialization_id_index');
        });

        Schema::table('add_drop_requests', function (Blueprint $table): void {
            $table->dropIndex('add_drop_requests_student_id_index');
        });

        Schema::table('suspension_requests', function (Blueprint $table): void {
            $table->dropIndex('suspension_requests_student_id_index');
        });

        Schema::table('discussion_requests', function (Blueprint $table): void {
            $table->dropIndex('discussion_requests_student_id_index');
        });

        Schema::table('notification_logs', function (Blueprint $table): void {
            $table->dropIndex('notification_logs_student_id_index');
        });

        Schema::table('course_surveys', function (Blueprint $table): void {
            $table->dropIndex('course_surveys_semester_id_index');
        });
    }
};
