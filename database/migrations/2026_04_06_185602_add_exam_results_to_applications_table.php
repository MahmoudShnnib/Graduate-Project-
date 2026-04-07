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
        Schema::table('applications', function (Blueprint $table) {
            $table->decimal('exam_score', 5, 2)->nullable()->after('exam_date');
            $table->boolean('exam_passed')->nullable()->after('exam_score');
            $table->dateTime('exam_result_date')->nullable()->after('exam_passed');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['exam_score', 'exam_passed', 'exam_result_date']);
        });
    }
};
