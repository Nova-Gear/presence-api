<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Presence extends Model
{
    use HasFactory;

    // Status constants
    const PRESENT = 'present';
    const ABSENT = 'absent';
    const LATE = 'late';
    const EARLY_LEAVE = 'early_leave';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'presence_type',
        'data',
        'address',
        'latitude',
        'longitude',
        'presence_time',
        'is_valid',
        'notes',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['work_duration'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'presence_time' => 'datetime',
        'is_valid' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * The possible status values.
     */
    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';
    const STATUS_EARLY_LEAVE = 'early_leave';

    /**
     * Get the user that owns the presence.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include today's presences.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('presence_time', Carbon::today());
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('presence_time', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by presence type.
     */
    public function scopeByPresenceType($query, $presenceType)
    {
        return $query->where('presence_type', $presenceType);
    }

    /**
     * Get today's checkin record for a user.
     */
    public static function getTodayCheckin($userId)
    {
        return static::where('user_id', $userId)
            ->where('type', 'checkin')
            ->whereDate('presence_time', Carbon::today())
            ->first();
    }

    /**
     * Get today's checkout record for a user.
     */
    public static function getTodayCheckout($userId)
    {
        return static::where('user_id', $userId)
            ->where('type', 'checkout')
            ->whereDate('presence_time', Carbon::today())
            ->first();
    }

    /**
     * Check if user is currently checked in (has checkin but no checkout today).
     */
    public static function isUserCheckedIn($userId)
    {
        $checkin = static::getTodayCheckin($userId);
        $checkout = static::getTodayCheckout($userId);
        
        return $checkin && !$checkout;
    }

    /**
     * Get the date of the presence.
     */
    public function getDateAttribute()
    {
        return $this->presence_time ? $this->presence_time->format('Y-m-d') : null;
    }

    /**
     * Get the work duration in minutes for this presence record.
     * Only applicable for checkin records.
     */
    public function getWorkDurationAttribute()
    {
        if ($this->type !== 'checkin') {
            return null;
        }

        $checkout = static::getTodayCheckout($this->user_id);
        if (!$checkout) {
            return null;
        }

        return $this->presence_time->diffInMinutes($checkout->presence_time);
    }
}
