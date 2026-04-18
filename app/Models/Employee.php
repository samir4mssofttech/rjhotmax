<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class Employee extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = [
        'profile_photo',
        'name',
        'email',
        'phone',
        'branch_id',
        'join_date',
        'confirmation_date',
        'salary',
        'skill_type',
        'payout_type',
        'exit_date',
        'basic_salary',
        'hra',
        'conveyance',
        'medical',
        'other_allowances',
        'pf',
        'esi',
        'employee_status',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'employee_status' => EmployeeStatus::class,
    ];
    /**
     * A Member can have many Customers.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * A Member can have one User.
     */
    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function getAccountNumberAttribute(): string
    {
        $prefix = 'RJ';

        $idPart = str_pad($this->id ?? 1, 4, '0', STR_PAD_LEFT);

        return $prefix . $idPart;
    }

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'confirmation_date' => 'date',
            'exit_date' => 'date',
            'status' => EmployeeStatus::class,
        ];
    }
}
