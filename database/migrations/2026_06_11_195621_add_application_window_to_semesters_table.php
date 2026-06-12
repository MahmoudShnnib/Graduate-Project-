<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table): void {
            // Master switch for accepting new public applications on /apply.
            // Default true preserves the prior always-open behaviour after deploy.
            $table->boolean('applications_open')->default(true)->after('survey_open');
            // Optional auto-close date: when set and passed, applications close even
            // if the switch is still on.
            $table->date('application_deadline')->nullable()->after('applications_open');
        });
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table): void {
            $table->dropColumn(['applications_open', 'application_deadline']);
        });
    }
};
