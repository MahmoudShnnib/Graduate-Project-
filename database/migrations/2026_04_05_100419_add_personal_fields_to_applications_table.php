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
        Schema::table('applications', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('user_id');
            $table->string('mother_name')->nullable()->after('last_name');
            $table->date('birth_date')->nullable()->after('mother_name');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('gender')->nullable()->after('birth_place');
            $table->string('marital_status')->nullable()->after('gender');
            $table->string('nationality')->nullable()->default('ليبية')->after('marital_status');
            $table->string('national_id')->nullable()->after('nationality');
            $table->string('card_number')->nullable()->after('national_id');
            $table->string('residence')->nullable()->after('card_number');
            $table->string('profession')->nullable()->after('residence');
            $table->string('workplace')->nullable()->after('profession');
            $table->text('permanent_address')->nullable()->after('workplace');
            $table->string('study_type')->nullable()->after('permanent_address');
            $table->string('delegation_number')->nullable()->after('study_type');
            $table->string('delegation_entity')->nullable()->after('delegation_number');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'last_name', 'mother_name', 'birth_date', 'birth_place',
                'gender', 'marital_status', 'nationality', 'national_id',
                'card_number', 'residence', 'profession', 'workplace',
                'permanent_address', 'study_type', 'delegation_number', 'delegation_entity',
            ]);
        });
    }
};
