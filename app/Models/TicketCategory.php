<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'name',
        'color_hex',
        'description',
        'price',
        'quota',
        'available_quota',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'quota' => 'integer',
        'available_quota' => 'integer',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function session()
    {
        return $this->belongsTo(EventSession::class , 'session_id');
    }

    public function seats()
    {
        return $this->hasMany(Seat::class , 'category_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class , 'category_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}