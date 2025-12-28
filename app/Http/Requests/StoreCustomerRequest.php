<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

// Store Customer Request
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // All authenticated users can create customers
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:Garage,Dealership,Distributor,Retailer'],
            'category' => ['required', 'in:A,A+,B,C'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'unique:customers,email', 'max:255'],
            'tax_id' => ['required', 'string', 'unique:customers,tax_id', 'max:50'],
            'payment_terms' => ['required', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'min:-90', 'max:90'],
            'longitude' => ['nullable', 'numeric', 'min:-180', 'max:180'],
            'address' => ['required', 'string', 'max:500'],
            'territory' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required',
            'type.required' => 'Customer type is required',
            'type.in' => 'Customer type must be: Garage, Dealership, Distributor, or Retailer',
            'category.required' => 'Customer category is required',
            'category.in' => 'Customer category must be: A, A+, B, or C',
            'email.required' => 'Email address is required',
            'email.unique' => 'This email is already registered',
            'tax_id.required' => 'Tax ID is required',
            'tax_id.unique' => 'This Tax ID is already registered',
            'payment_terms.max' => 'Payment terms cannot exceed 365 days',
            'credit_limit.required' => 'Credit limit is required',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Customer validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}


// Update Customer Request
class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // All authenticated users can update customers
    }

    public function rules(): array
    {
        $customerId = $this->route('id');
        
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:Garage,Dealership,Distributor,Retailer'],
            'category' => ['sometimes', 'in:A,A+,B,C'],
            'contact_person' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'unique:customers,email,' . $customerId, 'max:255'],
            'tax_id' => ['sometimes', 'string', 'unique:customers,tax_id,' . $customerId, 'max:50'],
            'payment_terms' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'min:-90', 'max:90'],
            'longitude' => ['nullable', 'numeric', 'min:-180', 'max:180'],
            'address' => ['sometimes', 'string', 'max:500'],
            'territory' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Customer type must be: Garage, Dealership, Distributor, or Retailer',
            'category.in' => 'Customer category must be: A, A+, B, or C',
            'email.unique' => 'This email is already registered to another customer',
            'tax_id.unique' => 'This Tax ID is already registered to another customer',
            'payment_terms.max' => 'Payment terms cannot exceed 365 days',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Customer validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}