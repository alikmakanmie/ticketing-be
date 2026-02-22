<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Seat extends Model
{
    use HasFactory;

    // ─── Status Constants (State Machine) ─────────────────────────────────────
    const STATUS_AVAILABLE = 'available';
    const STATUS_LOCKED = 'locked';
    const STATUS_BOOKED = 'booked';
    const STATUS_USED = 'used';

    protected $fillable = [
        'session_id',
        'category_id',
        'seat_code',
        'row_label',
        'seat_number',
        'status',
        'locked_by',
        'locked_until',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function session()
    {
        return $this->belongsTo(EventSession::class , 'session_id');
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class , 'category_id');
    }

    public function lockedByUser()
    {
        return $this->belongsTo(User::class , 'locked_by');
    }

    public function orderItem()
    {
        return $this->hasOne(OrderItem::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Cek apakah kursi ini bisa dipilih oleh user tertentu.
     * Kursi dianggap "tersedia" jika:
     *   - Statusnya memang 'available', ATAU
     *   - Statusnya 'locked' tapi sudah melewati waktu locked_until (kunci expired)
     */
    public function isAvailable(): bool
    {
        if ($this->status === self::STATUS_AVAILABLE) {
            return true;
        }

        if ($this->status === self::STATUS_LOCKED && $this->locked_until < Carbon::now()) {
            return true;
        }

        return false;
    }

    /**
     * Cek apakah kursi ini sedang dikunci oleh user tertentu (bukan orang lain).
     */
    public function isLockedByUser(int $userId): bool
    {
        return $this->status === self::STATUS_LOCKED
            && $this->locked_by === $userId
            && $this->locked_until > Carbon::now();
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)
            ->orWhere(function ($q) {
            $q->where('status', self::STATUS_LOCKED)
                ->where('locked_until', '<', Carbon::now());
        });
    }

    public function scopeExpiredLocks($query)
    {
        return $query->where('status', self::STATUS_LOCKED)
            ->where('locked_until', '<', Carbon::now());
    }
}