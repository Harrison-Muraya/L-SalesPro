<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerMapDataResource extends JsonResource
{
    /**
     * Transform the resource into an array for map visualization.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'credit_usage_percentage' => $this->credit_limit > 0 
                ? round(($this->current_balance / $this->credit_limit) * 100, 2) 
                : 0,
            'territory' => $this->territory,
        ];
    }
}