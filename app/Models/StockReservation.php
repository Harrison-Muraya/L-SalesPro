<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'order_id',
        'reservation_reference',
        'quantity',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '<', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
