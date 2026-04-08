<?php

namespace App\Models;

use App\Enums\Department;
use App\Enums\ApplicantStatus;
use App\Enums\Designation;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class Applicant extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = [
        'applicant_code',
        'applicant_name',
        'position',
        'gender',
        'date_of_birth',
        'designation',
        'aadhar_number',
        'pan_number',
        'apply_date',
        'branch_id',
        'city',
        'state',
        'location',
        'experience',
        'email_id',
        'mobile_number',
        'address',
        'date_of_joining',
        'salary',
        'status',
        //files
        'id_proof_type',
        'file_path',
        'appointment_letter_path',

        // ADD to $fillable:
        'employment_type',        // Full-time / Contract / Intern
        'contract_start_date',
        'contract_end_date',
        'contract_terms',

        'internship_start_date',
        'internship_end_date',
        'reporting_manager_id',   // FK to users table

        // Emergency contact
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',

        // Bank details
        'bank_account_number',
        'bank_name',
        'bank_ifsc_code',

        // Lifecycle dates
        'probation_end_date',
        'confirmation_date',
        'exit_date',
        'exit_reason',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'position' => Department::class,
            'designation' => Designation::class,
            'apply_date' => 'datetime',
            'date_of_joining' => 'datetime',
            'salary' => 'decimal:2',
            'status' => ApplicantStatus::class,

            // ADD to casts():
            'employment_type'    => EmploymentType::class,
            'probation_end_date' => 'datetime',
            'confirmation_date'  => 'datetime',
            'exit_date'          => 'datetime',
        ];
    }

    // ADD relation:
    public function reportingManager()
    {
        return $this->belongsTo(User::class, 'reporting_manager_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
