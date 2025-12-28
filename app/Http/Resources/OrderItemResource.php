<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\LeyscoHelpers;

class OrderItemResource extends JsonResource
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
                'category' => $this->product->category->name ?? null,
                'unit' => $this->product->unit,
                'packaging' => $this->product->packaging,
            ],
            'warehouse' => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ],
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'unit_price_formatted' => LeyscoHelpers::formatCurrency($this->unit_price),
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $this->discount_amount,
            'discount_amount_formatted' => LeyscoHelpers::formatCurrency($this->discount_amount),
            'subtotal' => $this->subtotal,
            'subtotal_formatted' => LeyscoHelpers::formatCurrency($this->subtotal),
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'tax_amount_formatted' => LeyscoHelpers::formatCurrency($this->tax_amount),
            'total' => $this->total,
            'total_formatted' => LeyscoHelpers::formatCurrency($this->total),
        ];
    }
}