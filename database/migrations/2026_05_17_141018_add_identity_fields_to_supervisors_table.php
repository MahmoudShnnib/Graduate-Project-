<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisors', function (Blueprint $table): void {
            $table->string('national_id')->nullable()->unique()->after('name');
            $table->date('birth_date')->nullable()->after('national_id');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('nationality')->nullable()->after('birth_place');
            $table->string('gender')->nullable()->after('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('supervisors', function (Blueprint $table): void {
            $table->dropUnique(['national_id']);
            $table->dropColumn(['national_id', 'birth_date', 'birth_place', 'nationality', 'gender']);
        });
    }
};
