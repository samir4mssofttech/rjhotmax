<?php

use App\Enums\ApplicantStatus;
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
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('applicant_code')->unique();
            $table->string('applicant_name');
            $table->string('position');
            $table->string('designation');
            $table->string('gender');
            $table->date('date_of_birth');
            $table->date('apply_date');
            $table->string('branch_name');
            $table->string('city');
            $table->string('state');
            $table->string('location');
            $table->string('experience');
            $table->string('email_id');
            $table->string('mobile_number');
            $table->text('address');
            $table->string('aadhar_number');
            $table->string('pan_number');
            $table->date('date_of_joining')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('status')->default(ApplicantStatus::INITIATED);


            //files
            $table->string('id_proof_type')->nullable();
            $table->string('file_path')->nullable();
            $table->string('appointment_letter_path')->nullable();
            
             // ── ➕ Job Information ────────────────────────────────────
            $table->string('employment_type')->nullable();           // EmploymentType enum
            $table->foreignId('reporting_manager_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // ── ➕ Emergency Contact ──────────────────────────────────
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            // ── ➕ Bank Details ───────────────────────────────────────
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_ifsc_code')->nullable();

            // ── ➕ Employee Lifecycle ─────────────────────────────────
            $table->date('probation_end_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('exit_date')->nullable();
            $table->text('exit_reason')->nullable();

            // ── Audit & Timestamps ───────────────────────────────────
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
        Schema::dropIfExists('applicants');
    }
};
