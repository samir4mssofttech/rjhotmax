<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

class Branch extends Model
{
    use SoftDeletes, Userstamps;

    protected $fillable = [
        'name',
        'code',
        'location',
        'is_active'
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class)
            ->wherePivort('branch_id', $this->id);
    }

    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->code . ')';
    }

    public function applicants()
    {
        return $this->hasMany(Applicant::class);
    }
}
