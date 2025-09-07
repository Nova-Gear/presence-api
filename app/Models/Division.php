<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'company_id',
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
     * Get the company that owns the division.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the users for the division.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope a query to only include active divisions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the division employees.
     */
    public function employees()
    {
        return $this->users()->where('role', 'employee');
    }

    /**
     * Get the division employee count.
     */
    public function getEmployeeCountAttribute()
    {
        return $this->users()->count();
    }
}
