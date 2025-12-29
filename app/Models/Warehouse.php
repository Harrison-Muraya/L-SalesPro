<?php

namespace App\Models;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'manager_email',
        'phone',
        'capacity',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    public function transfersFrom()
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function transfersTo()
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public function getTotalStockAttribute(): int
    {
        return $this->inventory()->sum('quantity');
    }

    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->capacity - $this->total_stock);
    }
}
