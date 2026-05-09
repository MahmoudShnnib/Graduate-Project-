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
        Schema::table('grades', function (Blueprint $table) {
            $table->timestamp('midterm_published_at')->nullable()->after('approved_at');
            $table->timestamp('published_at')->nullable()->after('midterm_published_at');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['midterm_published_at', 'published_at']);
        });
    }
};
