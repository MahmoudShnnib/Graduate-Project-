<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->decimal('coursework_grade', 5, 2);
            $table->decimal('final_grade', 5, 2);
            $table->decimal('total', 5, 2)->storedAs('coursework_grade + final_grade');
            $table->string('letter', 5)->nullable();
            $table->decimal('points', 3, 1)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_courses');
    }
};
