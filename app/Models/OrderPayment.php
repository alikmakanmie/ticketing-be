<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'payment_method',
        'bank_name',
        'account_number',
        'account_name',
        'transfer_proof',
        'transferred_at',
        'gateway_transaction_id',
        'gateway_payment_type',
        'gateway_response',
        'gateway_va_number',
        'status',
        'amount_paid',
        'verified_at',
    ];

    protected $casts = [
        'gateway_response' => 'array', // JSON otomatis di-decode/encode
        'transferred_at' => 'datetime',
        'verified_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}