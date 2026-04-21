<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });

        DB::table('students')
            ->where('status', 'suspended')
            ->update(['status' => 'inactive']);
    }

    public function down(): void
    {
        DB::table('students')
            ->where('status', 'inactive')
            ->update(['status' => 'suspended']);
    }
};
