<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    protected $fillable = [
        'name', 'slug', 'address', 'phone', 'email', 'status', 'plan',
        'trial_ends_at', 'plan_expires_at', 'max_rooms', 'max_users', 'admin_notes', 'backup_enabled',
        'owner_wa_consent',
    ];

    protected $casts = [
        'trial_ends_at'   => 'datetime',
        'plan_expires_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(HotelUser::class);
    }

    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
