<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanLog extends Model
{
    use HasFactory;

    const RESULT_SUCCESS = 'success';
    const RESULT_ALREADY_USED = 'already_used';
    const RESULT_INVALID_CODE = 'invalid_code';
    const RESULT_WRONG_SESSION = 'wrong_session';
    const RESULT_VOIDED = 'voided';
    const RESULT_ORDER_NOT_PAID = 'order_not_paid';

    protected $fillable = [
        'ticket_code_scanned',
        'ticket_id',
        'scanned_by',
        'result',
        'notes',
        'device_info',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function officer()
    {
        return $this->belongsTo(User::class , 'scanned_by');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isSuccess(): bool
    {
        return $this->result === self::RESULT_SUCCESS;
    }

    public function isAlreadyUsed(): bool
    {
        return $this->result === self::RESULT_ALREADY_USED;
    }
}