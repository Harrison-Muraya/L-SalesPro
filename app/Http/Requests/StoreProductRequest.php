<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;


// Store Product Request
class StoreProductRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        Log::info('current user ', ['user' => $this->user()]);
        return $this->user()->hasPermission('manage_inventory');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'unique:products,sku', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'packaging' => ['nullable', 'string', 'max:100'],
            'min_order_quantity' => ['required', 'integer', 'min:1'],
            'reorder_level' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Product category is required',
            'category_id.exists' => 'Selected category does not exist',
            'sku.required' => 'Product SKU is required',
            'sku.unique' => 'This SKU is already in use',
            'name.required' => 'Product name is required',
            'price.required' => 'Product price is required',
            'price.min' => 'Price must be greater than or equal to 0',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Product validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'You do not have permission to create products',
                'errors' => ['authorization' => ['Insufficient permissions']]
            ], 403)
        );
    }
}

// Update Product Request
class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('manage_inventory');
    }

    public function rules(): array
    {
        $productId = $this->route('id');
        
        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'sku' => ['sometimes', 'string', 'unique:products,sku,' . $productId, 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'packaging' => ['nullable', 'string', 'max:100'],
            'min_order_quantity' => ['sometimes', 'integer', 'min:1'],
            'reorder_level' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Product validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'You do not have permission to update products',
                'errors' => ['authorization' => ['Insufficient permissions']]
            ], 403)
        );
    }
}