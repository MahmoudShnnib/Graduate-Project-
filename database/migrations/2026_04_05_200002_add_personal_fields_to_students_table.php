<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('last_name', 100)->nullable()->after('user_id');
            $table->string('mother_name')->nullable()->after('last_name');
            $table->date('birth_date')->nullable()->after('mother_name');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('gender', 10)->nullable()->after('birth_place');
            $table->string('marital_status', 20)->nullable()->after('gender');
            $table->string('nationality', 100)->nullable()->after('marital_status');
            $table->string('national_id', 50)->nullable()->after('nationality');
            $table->string('card_number', 50)->nullable()->after('national_id');
            $table->string('residence')->nullable()->after('card_number');
            $table->string('permanent_address')->nullable()->after('residence');
            $table->string('profession')->nullable()->after('permanent_address');
            $table->string('workplace')->nullable()->after('profession');
            $table->string('study_type', 30)->nullable()->after('workplace');
            $table->string('delegation_number', 100)->nullable()->after('study_type');
            $table->string('delegation_entity')->nullable()->after('delegation_number');
            $table->boolean('is_transfer')->default(false)->after('delegation_entity');
            $table->string('degree_image')->nullable()->after('is_transfer');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn([
                'last_name', 'mother_name', 'birth_date', 'birth_place', 'gender',
                'marital_status', 'nationality', 'national_id', 'card_number', 'residence',
                'permanent_address', 'profession', 'workplace', 'study_type',
                'delegation_number', 'delegation_entity', 'is_transfer', 'degree_image',
            ]);
        });
    }
};
