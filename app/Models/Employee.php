<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
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
    // public function customers()
    // {
    //     return $this->hasMany(Customer::class);
    // }

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
