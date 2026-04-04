<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained('student_enrollments')->cascadeOnDelete();
            $table->decimal('coursework_grade', 5, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->decimal('total', 5, 2)->nullable();
            $table->string('letter')->nullable();
            $table->decimal('points', 3, 1)->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved'])->default('draft');
            $table->string('paper_sheet_path')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
