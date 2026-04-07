<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support altering CHECK constraints, so we recreate the table
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'initially_approved', 'accepted', 'rejected'])
                ->default('pending')
                ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'provisionally_accepted', 'accepted', 'rejected'])
                ->default('pending')
                ->after('user_id');
        });
    }
};
