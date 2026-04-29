<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->datetime('dismissed_at')->nullable()->after('warnings_count');
            $table->string('dismissal_type')->nullable()->after('dismissed_at');
            $table->foreignId('dismissal_course_id')->nullable()->after('dismissal_type')
                ->constrained('courses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['dismissal_course_id']);
            $table->dropColumn(['dismissed_at', 'dismissal_type', 'dismissal_course_id']);
        });
    }
};
