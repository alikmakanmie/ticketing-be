<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    const STATUS_ISSUED = 'issued';
    const STATUS_USED = 'used';
    const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'user_id',
        'ticket_code',
        'qr_code_path',
        'event_name_snapshot',
        'session_name_snapshot',
        'event_date_snapshot',
        'start_time_snapshot',
        'venue_snapshot',
        'seat_code_snapshot',
        'category_name_snapshot',
        'price_paid_snapshot',
        'status',
        'used_at',
        'scanned_by',
        'emailed_at',
        'whatsapp_sent_at',
    ];

    protected $casts = [
        'event_date_snapshot' => 'date',
        'price_paid_snapshot' => 'decimal:2',
        'used_at' => 'datetime',
        'emailed_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scannedByOfficer()
    {
        return $this->belongsTo(User::class , 'scanned_by');
    }

    public function scanLogs()
    {
        return $this->hasMany(ScanLog::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isValid(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isAlreadyUsed(): bool
    {
        return $this->status === self::STATUS_USED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }
}