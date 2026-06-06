<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Applications submitted before /apply started stamping `semester_id`
     * were saved with a NULL semester. Attach them to the earliest semester
     * so they remain reachable now that the admin views filter by semester.
     */
    public function up(): void
    {
        $earliestSemesterId = DB::table('semesters')->orderBy('id')->value('id');

        if ($earliestSemesterId !== null) {
            DB::table('applications')
                ->whereNull('semester_id')
                ->update(['semester_id' => $earliestSemesterId]);
        }
    }

    public function down(): void
    {
        // Irreversible data backfill — no rollback.
    }
};
