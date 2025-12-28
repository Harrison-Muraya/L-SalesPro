namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_sales');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.warehouse_id' => ['required', 'exists:warehouses,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer is required',
            'customer_id.exists' => 'Selected customer does not exist',
            'items.required' => 'Order must contain at least one item',
            'items.min' => 'Order must contain at least one item',
            'items.*.product_id.required' => 'Product is required for each item',
            'items.*.product_id.exists' => 'One or more products do not exist',
            'items.*.warehouse_id.required' => 'Warehouse is required for each item',
            'items.*.warehouse_id.exists' => 'One or more warehouses do not exist',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'discount_type.in' => 'Discount type must be either percentage or fixed',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Order validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'You do not have permission to create orders',
                'errors' => ['authorization' => ['Insufficient permissions']]
            ], 403)
        );
    }
}