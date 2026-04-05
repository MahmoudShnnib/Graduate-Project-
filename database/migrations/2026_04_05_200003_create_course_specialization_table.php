<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_specialization', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialization_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['general', 'specialized', 'elective'])->default('specialized');
            $table->timestamps();

            $table->unique(['course_id', 'specialization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_specialization');
    }
};
