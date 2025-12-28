<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\LeyscoHelpers;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'subcategory' => $this->subcategory,
            'description' => $this->description,
            'price' => $this->price,
            'price_formatted' => LeyscoHelpers::formatCurrency($this->price),
            'price_with_tax' => $this->price_with_tax,
            'price_with_tax_formatted' => LeyscoHelpers::formatCurrency($this->price_with_tax),
            'tax_rate' => $this->tax_rate,
            'unit' => $this->unit,
            'packaging' => $this->packaging,
            'min_order_quantity' => $this->min_order_quantity,
            'reorder_level' => $this->reorder_level,
            'total_stock' => $this->when(
                $request->routeIs('products.show') || $request->routeIs('products.stock'),
                $this->total_stock
            ),
            'total_reserved' => $this->when(
                $request->routeIs('products.show') || $request->routeIs('products.stock'),
                $this->total_reserved
            ),
            'is_low_stock' => $this->isLowStock(),
            'inventory' => $this->when(
                $request->routeIs('products.stock'),
                InventoryResource::collection($this->inventory)
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

// use Illuminate\Http\Resources\Json\ResourceCollection;

// class ProductCollection extends ResourceCollection
// {
//     /**
//      * Transform the resource collection into an array.
//      *
//      * @return array<int|string, mixed>
//      */
//     public function toArray(Request $request): array
//     {
//         return [
//             'data' => ProductResource::collection($this->collection),
//             'meta' => [
//                 'current_page' => $this->currentPage(),
//                 'from' => $this->firstItem(),
//                 'last_page' => $this->lastPage(),
//                 'per_page' => $this->perPage(),
//                 'to' => $this->lastItem(),
//                 'total' => $this->total(),
//             ],
//             'links' => [
//                 'first' => $this->url(1),
//                 'last' => $this->url($this->lastPage()),
//                 'prev' => $this->previousPageUrl(),
//                 'next' => $this->nextPageUrl(),
//             ],
//         ];
//     }
// }

class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse' => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
                'type' => $this->warehouse->type,
            ],
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'last_restock_date' => $this->last_restock_date?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}