<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The grade-sheet upload feature was removed, so both columns are now dead.
     */
    public function up(): void
    {
        Schema::table('course_offerings', function (Blueprint $table): void {
            $table->dropColumn('paper_sheet_path');
        });

        Schema::table('grades', function (Blueprint $table): void {
            $table->dropColumn('paper_sheet_path');
        });
    }

    public function down(): void
    {
        Schema::table('course_offerings', function (Blueprint $table): void {
            $table->string('paper_sheet_path')->nullable()->after('is_open');
        });

        Schema::table('grades', function (Blueprint $table): void {
            $table->string('paper_sheet_path')->nullable();
        });
    }
};
