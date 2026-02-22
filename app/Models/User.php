<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLE_BUYER = 'buyer';
    const ROLE_ADMIN = 'admin';
    const ROLE_FINANCE = 'finance';
    const ROLE_GATE_OFFICER = 'gate_officer';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ─── Helper Roles ──────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isFinance(): bool
    {
        return $this->role === self::ROLE_FINANCE;
    }

    public function isGateOfficer(): bool
    {
        return $this->role === self::ROLE_GATE_OFFICER;
    }

    public function isBuyer(): bool
    {
        return $this->role === self::ROLE_BUYER;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_FINANCE, self::ROLE_GATE_OFFICER]);
    }

    // ─── Relasi ────────────────────────────────────────────────────────────────

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function scanLogs()
    {
        return $this->hasMany(ScanLog::class , 'scanned_by');
    }

    public function createdEvents()
    {
        return $this->hasMany(Event::class , 'created_by');
    }

    public function lockedSeats()
    {
        return $this->hasMany(Seat::class , 'locked_by');
    }
}