<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\LeyscoHelpers;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'type' => $this->customer->type,
                'category' => $this->customer->category,
                'contact_person' => $this->customer->contact_person,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ],
            'created_by' => [
                'id' => $this->createdBy->id,
                'username' => $this->createdBy->username,
                'full_name' => $this->createdBy->full_name,
                'role' => $this->createdBy->role,
            ],
            'status' => $this->status,
            'items' => $this->when(
                $request->routeIs('orders.show') || $request->routeIs('orders.invoice'),
                OrderItemResource::collection($this->whenLoaded('items'))
            ),
            'items_count' => $this->items->count(),
            'subtotal' => $this->subtotal,
            'subtotal_formatted' => LeyscoHelpers::formatCurrency($this->subtotal),
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $this->discount_amount,
            'discount_amount_formatted' => LeyscoHelpers::formatCurrency($this->discount_amount),
            'tax_amount' => $this->tax_amount,
            'tax_amount_formatted' => LeyscoHelpers::formatCurrency($this->tax_amount),
            'total_amount' => $this->total_amount,
            'total_amount_formatted' => LeyscoHelpers::formatCurrency($this->total_amount),
            'notes' => $this->notes,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}