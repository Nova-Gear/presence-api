<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'plan_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the plan that owns the company.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the divisions for the company.
     */
    public function divisions()
    {
        return $this->hasMany(Division::class);
    }

    /**
     * Get the users for the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the presence config for the company.
     */
    public function presenceConfig()
    {
        return $this->hasOne(PresenceConfig::class);
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the company admins.
     */
    public function admins()
    {
        return $this->users()->where('role', 'admin_company');
    }

    /**
     * Get the company employees.
     */
    public function employees()
    {
        return $this->users()->where('role', 'employee');
    }

    /**
     * Check if company has reached employee limit.
     */
    public function hasReachedEmployeeLimit()
    {
        if (!$this->plan || !$this->plan->employee_limit) {
            return false;
        }

        return $this->users()->count() >= $this->plan->employee_limit;
    }
}
