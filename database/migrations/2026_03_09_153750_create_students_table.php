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
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('student_number')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('specialization_id')->constrained();

            $table->foreignId('application_id')->constrained()->cascadeOnDelete();

            $table->year('admission_year');

            $table->tinyInteger('semester_type');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
