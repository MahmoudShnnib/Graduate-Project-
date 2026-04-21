<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a rejected_at timestamp so the admin can mark a surveyed course
     * as rejected (distinct from "accepted & enrolled"). Both cases still
     * set is_processed=true; the timestamp discriminates which path.
     *
     *   rejected_at IS NULL     → either pending (is_processed=false)
     *                             OR accepted+enrolled (is_processed=true)
     *   rejected_at IS NOT NULL → admin explicitly rejected this choice
     *
     * The student portal uses this to show a "مرفوض" badge on their survey page.
     */
    public function up(): void
    {
        Schema::table('course_surveys', function (Blueprint $table): void {
            $table->timestamp('rejected_at')->nullable()->after('is_processed');
        });
    }

    public function down(): void
    {
        Schema::table('course_surveys', function (Blueprint $table): void {
            $table->dropColumn('rejected_at');
        });
    }
};
