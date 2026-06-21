<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admission documents (bachelor certificate + remedial statement) were
     * captured on the application but lost on approval — the students table
     * had no columns for them. Add them, then backfill from each student's
     * originating application so already-approved students keep their files.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('bachelor_certificate')->nullable()->after('degree_image');
            $table->string('remedial_statement')->nullable()->after('bachelor_certificate');
        });

        DB::table('applications')
            ->whereNotNull('user_id')
            ->where(function ($query) {
                $query->whereNotNull('bachelor_certificate')
                    ->orWhereNotNull('remedial_statement');
            })
            ->get(['user_id', 'bachelor_certificate', 'remedial_statement'])
            ->each(function ($application) {
                DB::table('students')
                    ->where('user_id', $application->user_id)
                    ->update([
                        'bachelor_certificate' => $application->bachelor_certificate,
                        'remedial_statement' => $application->remedial_statement,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['bachelor_certificate', 'remedial_statement']);
        });
    }
};
