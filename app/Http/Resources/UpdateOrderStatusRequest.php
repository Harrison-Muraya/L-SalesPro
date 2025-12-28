<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Get order ID from route
        $orderId = $this->route('id');
        $order = \App\Models\Order::find($orderId);
        
        if (!$order) {
            return false;
        }
        
        // Managers can update any order
        if ($this->user()->isManager()) {
            return true;
        }
        
        // Representatives can only update their own orders
        return $order->created_by === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,confirmed,processing,shipped,delivered,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Order status is required',
            'status.in' => 'Invalid order status. Must be one of: pending, confirmed, processing, shipped, delivered, cancelled',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Status validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this order',
                'errors' => ['authorization' => ['Insufficient permissions']]
            ], 403)
        );
    }
}