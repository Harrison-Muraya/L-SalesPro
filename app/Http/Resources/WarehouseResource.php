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
                function() {
                    try {
                        return $this->inventory()->count();
                    } catch (\Exception $e) {
                        return 0;
                    }
                }
            ),
            'total_stock' => $this->when(
                $request->routeIs('warehouses.show'),
                function() {
                    try {
                        return $this->inventory()->sum('quantity');
                    } catch (\Exception $e) {
                        return 0;
                    }
                }
            ),
            'available_stock' => $this->when(
                $request->routeIs('warehouses.show'),
                function() {
                    try {
                        return $this->inventory()->sum('available_quantity');
                    } catch (\Exception $e) {
                        return 0;
                    }
                }
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
