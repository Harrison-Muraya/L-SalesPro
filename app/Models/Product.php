<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'products';
    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'subcategory',
        'description',
        'price',
        'tax_rate',
        'unit',
        'packaging',
        'min_order_quantity',
        'reorder_level',
    ];

        protected $casts = [
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'min_order_quantity' => 'integer',
        'reorder_level' => 'integer',
    ];

    /**
     * Category relationship
     */
    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    /**
     * Inventory across all warehouses
     */
    public function inventory()
    {
        return $this->hasMany(\App\Models\Inventory::class);
    }

    /**
     * Order items
     */
    public function orderItems()
    {
        return $this->hasMany(\App\Models\OrderItem::class);
    }

    /**
     * Stock reservations
     */
    public function stockReservations()
    {
        return $this->hasMany(\App\Models\StockReservation::class);
    }

    /**
     * Get total available stock across all warehouses
     */
    public function getTotalStockAttribute(): int
    {
        return $this->inventory()->sum('available_quantity');
    }

    /**
     * Get total reserved stock
     */
    public function getTotalReservedAttribute(): int
    {
        return $this->inventory()->sum('reserved_quantity');
    }

    /**
     * Check if product is low on stock
     */
    public function isLowStock(): bool
    {
        return $this->total_stock <= $this->reorder_level;
    }

    /**
     * Get stock at specific warehouse
     */
    public function getStockAtWarehouse(int $warehouseId): ?Inventory
    {
        return $this->inventory()
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    /**
     * Check availability at warehouse
     */
    public function isAvailableAtWarehouse(int $warehouseId, int $quantity): bool
    {
        $inventory = $this->getStockAtWarehouse($warehouseId);
        
        if (!$inventory) {
            return false;
        }

        return $inventory->available_quantity >= $quantity;
    }

    /**
     * Calculate price with tax
     */
    public function getPriceWithTaxAttribute(): float
    {
        return $this->price + ($this->price * $this->tax_rate / 100);
    }

    /**
     * Scope: Low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->whereRaw('available_quantity <= products.reorder_level');
        });
    }

    /**
     * Scope: Search products
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    /**
     * Scope: Filter by category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: Filter by price range
     */
    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }
}
