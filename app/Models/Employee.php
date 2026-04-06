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
        'name',
        'email',
        'phone',
        'branch_id',
        'join_date',
        'confirmation_date',
        'exit_date',
        'status',
    ];
    protected $casts = [
        'status' => 'boolean',
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
