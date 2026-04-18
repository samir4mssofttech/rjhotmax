<?php

use App\Enums\EmployeeStatus;
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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('profile_photo')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('join_date')->nullable();

          
            $table->date('confirmation_date')->nullable();
            $table->unsignedBigInteger('salary')->nullable()->comment('Stored in paisa (1₹ = 100)');
            $table->string('skill_type')->nullable();
            $table->string('payout_type')->nullable();
            $table->date('exit_date')->nullable();

            $table->unsignedBigInteger('basic_salary')->nullable()->comment('Stored in paisa (1₹ = 100)');
            $table->unsignedBigInteger('hra')->nullable()->comment('Stored in paisa (1₹ = 100)');
            $table->unsignedBigInteger('conveyance')->nullable()->comment('Stored in paisa (1₹ = 100)');
            $table->unsignedBigInteger('medical')->nullable()->comment('Stored in paisa (1₹ = 100)');
            $table->unsignedBigInteger('other_allowances')->nullable()->comment('Stored in paisa (1₹ = 100)');

            $table->unsignedBigInteger('pf')->nullable()->comment('Stored in percentage (1 = 100%)');
            $table->unsignedBigInteger('esi')->nullable()->comment('Stored in percentage (1 = 100%)');

            $table->enum('employee_status', EmployeeStatus::cases())->default(EmployeeStatus::ACTIVE->value);
            $table->boolean('is_active')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
