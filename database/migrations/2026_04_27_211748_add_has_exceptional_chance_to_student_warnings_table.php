<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_warnings', function (Blueprint $table) {
            $table->boolean('has_exceptional_chance')->default(false)->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_warnings', function (Blueprint $table) {
            $table->dropColumn('has_exceptional_chance');
        });
    }
};
