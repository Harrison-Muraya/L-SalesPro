<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
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
            'product' => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
            ],
            'from_warehouse' => [
                'id' => $this->fromWarehouse->id,
                'code' => $this->fromWarehouse->code,
                'name' => $this->fromWarehouse->name,
            ],
            'to_warehouse' => [
                'id' => $this->toWarehouse->id,
                'code' => $this->toWarehouse->code,
                'name' => $this->toWarehouse->name,
            ],
            'requested_by' => [
                'id' => $this->requestedBy->id,
                'username' => $this->requestedBy->username,
                'full_name' => $this->requestedBy->full_name,
            ],
            'quantity' => $this->quantity,
            'status' => $this->status,
            'notes' => $this->notes,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}