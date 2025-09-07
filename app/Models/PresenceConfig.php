<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PresenceConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'checkin_start',
        'checkin_end',
        'checkout_start',
        'checkout_end',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checkin_start' => 'datetime:H:i:s',
        'checkin_end' => 'datetime:H:i:s',
        'checkout_start' => 'datetime:H:i:s',
        'checkout_end' => 'datetime:H:i:s',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the presence config.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include active configs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if the current time is within checkin window.
     */
    public function isCheckinTimeValid($time = null)
    {
        $time = $time ?: Carbon::now();
        $currentTime = Carbon::parse($time->format('H:i:s'));
        $checkinStart = Carbon::parse($this->checkin_start);
        $checkinEnd = Carbon::parse($this->checkin_end);

        return $currentTime->between($checkinStart, $checkinEnd);
    }

    /**
     * Check if the current time is within checkout window.
     */
    public function isCheckoutTimeValid($time = null)
    {
        $time = $time ?: Carbon::now();
        $currentTime = Carbon::parse($time->format('H:i:s'));
        $checkoutStart = Carbon::parse($this->checkout_start);
        $checkoutEnd = Carbon::parse($this->checkout_end);

        return $currentTime->between($checkoutStart, $checkoutEnd);
    }

    /**
     * Check if checkin is late.
     */
    public function isCheckinLate($time = null)
    {
        $time = $time ?: Carbon::now();
        $currentTime = Carbon::parse($time->format('H:i:s'));
        $checkinEnd = Carbon::parse($this->checkin_end);

        return $currentTime->gt($checkinEnd);
    }

    /**
     * Check if checkout is early.
     */
    public function isCheckoutEarly($time = null)
    {
        $time = $time ?: Carbon::now();
        $currentTime = Carbon::parse($time->format('H:i:s'));
        $checkoutStart = Carbon::parse($this->checkout_start);

        return $currentTime->lt($checkoutStart);
    }

    /**
     * Get the formatted checkin window.
     */
    public function getCheckinWindowAttribute()
    {
        return Carbon::parse($this->checkin_start)->format('H:i') . ' - ' . Carbon::parse($this->checkin_end)->format('H:i');
    }

    /**
     * Get the formatted checkout window.
     */
    public function getCheckoutWindowAttribute()
    {
        return Carbon::parse($this->checkout_start)->format('H:i') . ' - ' . Carbon::parse($this->checkout_end)->format('H:i');
    }

    /**
     * Get the next valid checkin time.
     */
    public function getNextCheckinTime()
    {
        $tomorrow = Carbon::tomorrow();
        return $tomorrow->setTimeFromTimeString($this->checkin_start);
    }

    /**
     * Get the next valid checkout time.
     */
    public function getNextCheckoutTime()
    {
        $today = Carbon::today();
        $checkoutStart = $today->copy()->setTimeFromTimeString($this->checkout_start);
        
        if (Carbon::now()->gt($checkoutStart)) {
            return Carbon::tomorrow()->setTimeFromTimeString($this->checkout_start);
        }
        
        return $checkoutStart;
    }
}
