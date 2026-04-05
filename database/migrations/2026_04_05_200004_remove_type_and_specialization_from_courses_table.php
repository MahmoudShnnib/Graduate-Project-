<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropForeign(['specialization_id']);
            $table->dropColumn(['type', 'specialization_id']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->enum('type', ['general', 'specialized', 'elective'])->after('credits');
            $table->foreignId('specialization_id')->nullable()->constrained()->nullOnDelete()->after('type');
        });
    }
};
