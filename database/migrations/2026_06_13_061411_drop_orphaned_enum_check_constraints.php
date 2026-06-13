<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tables whose `enum()`-born CHECK constraints have drifted from (or are at
     * risk of drifting from) the application-layer backed enums.
     *
     * On PostgreSQL `$table->enum()` compiles to a CHECK constraint that later
     * value/column changes (renames, `->change()` to varchar) do NOT keep in
     * sync — e.g. the live `students_status_check` still only allowed the
     * original four values while the StudentStatus enum now emits six, crashing
     * any write of `inactive`/`on_hold`/`transferred`/`dismissed`. The backed
     * enum cast is the single source of truth, so we drop the stale DB-level
     * checks entirely.
     *
     * No-op on SQLite (no such constraint exists) and MySQL (its native ENUM is
     * altered directly by the column migrations).
     *
     * @var list<string>
     */
    private array $tables = [
        'semesters',
        'applications',
        'students',
        'courses',
        'student_enrollments',
        'grades',
        'suspension_requests',
        'thesis_registrations',
        'clearance_requests',
        'notification_logs',
        'add_drop_requests',
        'course_specialization',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Inlined as literals (not bound params): $this->tables is a hardcoded
        // constant list — no user input — so there is no injection surface, and
        // a plain `IN ('a','b',...)` is the most portable, least-surprising SQL.
        $tableList = collect($this->tables)
            ->map(fn (string $table): string => "'".$table."'")
            ->implode(', ');

        $constraints = DB::select(
            "SELECT rel.relname AS table_name, con.conname AS constraint_name
             FROM pg_constraint con
             JOIN pg_class rel ON rel.oid = con.conrelid
             JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
             WHERE con.contype = 'c'
               AND nsp.nspname = current_schema()
               AND rel.relname IN ({$tableList})"
        );

        foreach ($constraints as $constraint) {
            DB::statement(sprintf(
                'ALTER TABLE "%s" DROP CONSTRAINT IF EXISTS "%s"',
                $constraint->table_name,
                $constraint->constraint_name,
            ));
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: re-adding the stale CHECK constraints
        // would reintroduce the production crash this migration fixes.
    }
};
