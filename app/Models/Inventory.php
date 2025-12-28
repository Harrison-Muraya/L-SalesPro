<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    protected $table = 'inventorys';
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'last_restock_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'last_restock_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate available quantity
        static::saving(function ($inventory) {
            $inventory->available_quantity = $inventory->quantity - $inventory->reserved_quantity;
        });
    }
}
