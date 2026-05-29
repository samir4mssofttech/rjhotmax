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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('pf_number')
                ->nullable()
                ->after('pf');

            $table->string('esi_number')
                ->nullable()
                ->after('esi');
            $table->string('uan_number')
                ->nullable()
                ->after('esi_number');
            $table->string('designation')
                ->after('skill_type');
            $table->date('dob')->nullable()->after('join_date');
            $table->string('gender')->nullable()->after('dob');
            $table->string('pan_number')->nullable()->after('designation');
            $table->string('aadhar_number')->nullable()->after('pan_number');
            $table->string('bank_account_number')->nullable()->after('aadhar_number');
            $table->string('bank_name')->nullable()->after('bank_account_number');
            $table->string('ifsc_code')->nullable()->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'dob',
                'gender',
                'designation',
                'pan_number',
                'aadhar_number',
                'bank_account_number',
                'bank_name',
                'ifsc_code',
                'pf_number',
                'esi_number',
                'uan_number',
            ]);
        });
    }
};
