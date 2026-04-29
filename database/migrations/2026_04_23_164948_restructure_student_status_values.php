<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Order matters: rename 'inactive' → 'on_hold' BEFORE any other change,
        // so the 'inactive' value is free to be reused for the new concept.
        DB::table('students')->where('status', 'inactive')->update(['status' => 'on_hold']);
        DB::table('students')->where('status', 'withdrawn')->update(['status' => 'transferred']);
    }

    public function down(): void
    {
        DB::table('students')->where('status', 'on_hold')->update(['status' => 'inactive']);
        DB::table('students')->where('status', 'transferred')->update(['status' => 'withdrawn']);
    }
};
