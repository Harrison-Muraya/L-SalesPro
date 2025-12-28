<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


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