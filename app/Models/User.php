<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',
        'division_id',
        'employee_id',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'company_id' => $this->company_id,
            'division_id' => $this->division_id,
        ];
    }

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the division that owns the user.
     */
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Get the presences for the user.
     */
    public function presences()
    {
        return $this->hasMany(Presence::class);
    }

    /**
     * Get the manual presence requests for the user.
     */
    public function manualPresenceRequests()
    {
        return $this->hasMany(ManualPresenceRequest::class);
    }

    /**
     * Get the manual presence requests approved by the user.
     */
    public function approvedRequests()
    {
        return $this->hasMany(ManualPresenceRequest::class, 'approved_by');
    }

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is company admin.
     */
    public function isCompanyAdmin()
    {
        return $this->role === 'admin_company';
    }

    /**
     * Check if user is employee.
     */
    public function isEmployee()
    {
        return $this->role === 'employee';
    }
}
