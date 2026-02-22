<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventSession extends Model
{
    use HasFactory;

    const STATUS_UPCOMING = 'upcoming';
    const STATUS_OPEN = 'open';
    const STATUS_SOLD_OUT = 'sold_out';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_ENDED = 'ended';

    protected $fillable = [
        'event_id',
        'name',
        'event_date',
        'start_time',
        'end_time',
        'total_seats',
        'available_seats',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketCategories()
    {
        return $this->hasMany(TicketCategory::class , 'session_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class , 'session_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class , 'session_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isSoldOut(): bool
    {
        return $this->available_seats <= 0;
    }
}