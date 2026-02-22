<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seat_id',
        'category_id',
        'category_name_snapshot',
        'price_snapshot',
        'seat_code_snapshot',
    ];

    protected $casts = [
        'price_snapshot' => 'decimal:2',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class , 'category_id');
    }

    public function ticket()
    {
        return $this->hasOne(Ticket::class);
    }
}