<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\LeyscoHelpers;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $availableCredit = LeyscoHelpers::calculateAvailableCredit(
            // $this->credit_limit,
            // $this->current_balance
                $creditLimit = (float) ($this->credit_limit ?? 0),
                $currentBalance = (float) ($this->current_balance ?? 0)
        );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'tax_id' => $this->tax_id,
            'payment_terms' => $this->payment_terms,
            'payment_terms_label' => $this->payment_terms . ' days',
            'credit_limit' => $creditLimit,
            'credit_limit_formatted' => LeyscoHelpers::formatCurrency($this->credit_limit),
            'current_balance' => $currentBalance,
            'current_balance_formatted' => LeyscoHelpers::formatCurrency($this->current_balance),
            'available_credit' => $availableCredit,
            'available_credit_formatted' => LeyscoHelpers::formatCurrency($availableCredit),
            'credit_usage_percentage' => $this->credit_limit > 0 
                ? round(($this->current_balance / $this->credit_limit) * 100, 2) 
                : 0,
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'address' => $this->address,
            ],
            'territory' => $this->territory,
            'total_orders' => $this->when(
                $request->routeIs('customers.show'),
                function() {
                    try {
                        return $this->orders()->count();
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