<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\Department;
use App\Enums\Designation;
use App\Enums\Gender;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class User extends Authenticatable implements HasName
{
    use HasFactory, Notifiable, SoftDeletes, Userstamps;

    protected $fillable = [
        'full_name',
        'email',
        'user_role',
        'employee_code',
        'department',
        'designation',
        'password',
        'mobile_number',
        'date_of_birth',
        'gender',
        'aadhaar_number',
        'pan_number',
        'state',
        'city',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'datetime',
            'user_role' => UserRole::class,
            'department' => Department::class,
            'designation' => Designation::class,
            'gender' => Gender::class,
            'status' => UserStatus::class,
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function isAdmin(): bool
    {
        return $this->user_role === UserRole::ADMIN;
    }

    public function isHR(): bool
    {
        return $this->user_role === UserRole::HR;
    }

    public function isAgent(): bool
    {
        return $this->user_role === UserRole::AGENT;
    }

    public function isEmployee(): bool
    {
        return $this->user_role === UserRole::EMPLOYEE;
    }
}
