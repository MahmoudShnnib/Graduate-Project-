<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thesis_registrations', function (Blueprint $table) {
            $table->dropUnique('thesis_registrations_student_id_unique');
            $table->index('student_id');

            $table->string('title_en')->nullable()->after('title');
            $table->foreignId('supervisor_id')->nullable()->after('supervisor_employer')
                ->constrained('supervisors')->nullOnDelete();
            $table->foreignId('faculty_supervisor_id')->nullable()->after('supervisor_id')
                ->constrained('users')->nullOnDelete();
            $table->boolean('is_external_supervisor')->default(false)->after('faculty_supervisor_id');
            $table->string('proposal_path')->nullable()->after('official_form_path');
            $table->text('rejection_reason')->nullable()->after('proposal_path');
            $table->timestamp('printed_at')->nullable()->after('rejection_reason');

            $table->string('supervisor_name')->nullable()->change();
            $table->string('supervisor_degree')->nullable()->change();
            $table->string('supervisor_employer')->nullable()->change();
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('thesis_registrations', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropForeign(['faculty_supervisor_id']);
            $table->dropColumn([
                'title_en',
                'supervisor_id',
                'faculty_supervisor_id',
                'is_external_supervisor',
                'proposal_path',
                'rejection_reason',
                'printed_at',
            ]);

            $table->dropIndex(['student_id']);
            $table->unique('student_id');
        });
    }
};
