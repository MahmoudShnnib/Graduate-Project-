<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->datetime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'type', 'is_active']);
            $table->index(['student_id', 'course_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_warnings');
    }
};
