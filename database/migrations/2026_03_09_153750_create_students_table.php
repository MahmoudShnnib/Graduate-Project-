<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('enrollment_no')->unique()->nullable();
            $table->foreignId('specialization_id')->constrained();
            $table->enum('status', ['active', 'suspended', 'withdrawn', 'graduated'])->default('active');
            $table->decimal('gpa', 3, 2)->default(0.00);
            $table->integer('warnings_count')->default(0);
            $table->integer('suspension_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
