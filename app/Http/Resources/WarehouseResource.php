<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'manager_email' => $this->manager_email,
            'phone' => $this->phone,
            'capacity' => $this->capacity,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'inventory_count' => $this->when(
                $request->routeIs('warehouses.show'),
                fn() => $this->inventory()->count()
            ),
            'total_stock' => $this->when(
                $request->routeIs('warehouses.show'),
                fn() => $this->inventory()->sum('quantity')
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}