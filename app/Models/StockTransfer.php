<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'requested_by',
        'quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'transferred_at' => 'datetime',
    ];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }
    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
    
}
