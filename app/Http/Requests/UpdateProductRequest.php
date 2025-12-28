<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;



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