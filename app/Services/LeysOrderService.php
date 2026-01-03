<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Inventory;
use App\Helpers\LeyscoHelpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendOrderConfirmationEmail;
use App\Services\LeysInventoryService;
use Exception;

class LeysOrderService
{
    public function __construct(
        private LeysInventoryService $inventoryService
    ) {}

    /**
     * Create a new order with all business logic
     * 
     * @param array $data
     * @return Order
     * @throws Exception
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // 1. Validate customer credit limit
            $customer = Customer::findOrFail($data['customer_id']);
            $this->validateCreditLimit($customer, $data['items']);

            // 2. Generate order number
            $orderNumber = LeyscoHelpers::generateOrderNumber();

            // 3. Create order
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'created_by' => Auth::id(),
                'status' => 'pending',
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            // 4. Process order items
            $subtotal = 0;
            $taxAmount = 0;

            foreach ($data['items'] as $itemData) {
                // Check product availability
                $product = Product::findOrFail($itemData['product_id']);
                $this->validateProductAvailability(
                    $product,
                    $itemData['warehouse_id'],
                    $itemData['quantity']
                );

                // Reserve stock
                $this->inventoryService->reserveStock(
                    $product->id,
                    $itemData['warehouse_id'],
                    $itemData['quantity'],
                    $order->id
                );

                // Calculate line item pricing
                $lineCalculations = $this->calculateLineItem($itemData, $product);

                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'warehouse_id' => $itemData['warehouse_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'] ?? $product->price,
                    'discount_type' => $itemData['discount_type'] ?? null,
                    'discount_value' => $itemData['discount_value'] ?? 0,
                    'discount_amount' => $lineCalculations['discount_amount'],
                    'subtotal' => $lineCalculations['subtotal'],
                    'tax_rate' => $product->tax_rate,
                    'tax_amount' => $lineCalculations['tax_amount'],
                    'total' => $lineCalculations['total'],
                ]);

                $subtotal += $lineCalculations['subtotal'];
                $taxAmount += $lineCalculations['tax_amount'];
            }

            // 5. Calculate order-level discount
            $orderDiscount = 0;
            if (isset($data['discount_type'])) {
                $orderDiscount = LeyscoHelpers::calculateDiscount(
                    $subtotal,
                    $data['discount_type'],
                    $data['discount_value']
                );
            }

            // 6. Calculate final totals
            $subtotalAfterDiscount = $subtotal - $orderDiscount;
            $totalAmount = $subtotalAfterDiscount + $taxAmount;

            // 7. Update order with calculations
            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $orderDiscount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ]);

            // 8. Update customer balance
            $customer->increment('current_balance', $totalAmount);

            // 9. Queue order confirmation email
            // SendOrderConfirmationEmail::dispatch($order);

            return $order->load(['items.product', 'customer']);
        });
    }

    /**
     * Update order status with validation
     * 
     * @param Order $order
     * @param string $newStatus
     * @return Order
     * @throws Exception
     */
    public function updateOrderStatus(Order $order, string $newStatus): Order
    {
        $validTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => []
        ];

        if (!in_array($newStatus, $validTransitions[$order->status])) {
            throw new Exception(
                "Invalid status transition from {$order->status} to {$newStatus}"
            );
        }

        DB::transaction(function () use ($order, $newStatus) {
            $order->update(['status' => $newStatus]);

            // Update timestamps based on status
            switch ($newStatus) {
                case 'confirmed':
                    $order->update(['confirmed_at' => now()]);
                    // Confirm all stock reservations
                    $this->inventoryService->confirmReservations($order->id);
                    break;
                case 'shipped':
                    $order->update(['shipped_at' => now()]);
                    break;
                case 'delivered':
                    $order->update(['delivered_at' => now()]);
                    break;
                case 'cancelled':
                    // Release all reserved stock
                    $this->inventoryService->releaseOrderReservations($order->id);
                    // Reduce customer balance
                    $order->customer->decrement('current_balance', $order->total_amount);
                    break;
            }
        });

        return $order->fresh();
    }

    /**
     * Calculate order total preview without creating order
     * 
     * @param array $data
     * @return array
     */
    public function calculateOrderTotal(array $data): array
    {
        $subtotal = 0;
        $totalTax = 0;
        $items = [];

        foreach ($data['items'] as $itemData) {
            $product = Product::findOrFail($itemData['product_id']);
            $lineCalc = $this->calculateLineItem($itemData, $product);
            
            $subtotal += $lineCalc['subtotal'];
            $totalTax += $lineCalc['tax_amount'];
            
            $items[] = array_merge($itemData, $lineCalc, [
                'product_name' => $product->name,
            ]);
        }

        // Order-level discount
        $orderDiscount = 0;
        if (isset($data['discount_type'])) {
            $orderDiscount = LeyscoHelpers::calculateDiscount(
                $subtotal,
                $data['discount_type'],
                $data['discount_value']
            );
        }

        $subtotalAfterDiscount = $subtotal - $orderDiscount;
        $total = $subtotalAfterDiscount + $totalTax;

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'order_discount' => $orderDiscount,
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'tax_amount' => $totalTax,
            'total_amount' => $total,
            'formatted' => [
                'subtotal' => LeyscoHelpers::formatCurrency($subtotal),
                'order_discount' => LeyscoHelpers::formatCurrency($orderDiscount),
                'tax_amount' => LeyscoHelpers::formatCurrency($totalTax),
                'total_amount' => LeyscoHelpers::formatCurrency($total),
            ]
        ];
    }

    /**
     * Generate invoice data for an order
     * 
     * @param Order $order
     * @return array
     */
    public function generateInvoice(Order $order): array
    {
        $order->load(['items.product', 'customer', 'createdBy']);

        return [
            'order_number' => $order->order_number,
            'order_date' => $order->created_at->format('Y-m-d'),
            'status' => $order->status,
            'customer' => [
                'name' => $order->customer->name,
                'contact_person' => $order->customer->contact_person,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
                'address' => $order->customer->address,
                'tax_id' => $order->customer->tax_id,
            ],
            'items' => $order->items->map(function ($item) {
                return [
                    'product_name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'subtotal' => $item->subtotal,
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => $item->tax_amount,
                    'total' => $item->total,
                    'formatted' => [
                        'unit_price' => LeyscoHelpers::formatCurrency($item->unit_price),
                        'total' => LeyscoHelpers::formatCurrency($item->total),
                    ]
                ];
            }),
            'summary' => [
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'total_amount' => $order->total_amount,
                'formatted' => [
                    'subtotal' => LeyscoHelpers::formatCurrency($order->subtotal),
                    'discount_amount' => LeyscoHelpers::formatCurrency($order->discount_amount),
                    'tax_amount' => LeyscoHelpers::formatCurrency($order->tax_amount),
                    'total_amount' => LeyscoHelpers::formatCurrency($order->total_amount),
                ]
            ],
            'payment_terms' => $order->customer->payment_terms . ' days',
            'created_by' => $order->createdBy->full_name ?? 'System',
        ];
    }

    /**
     * Validate customer credit limit
     * 
     * @param Customer $customer
     * @param array $items
     * @throws Exception
     */
    private function validateCreditLimit(Customer $customer, array $items): void
    {
        $orderTotal = 0;
        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $unitPrice = $item['unit_price'] ?? $product->price;
            $orderTotal += $unitPrice * $item['quantity'];
        }

        $availableCredit = LeyscoHelpers::calculateAvailableCredit(
            $customer->credit_limit,
            $customer->current_balance
        );

        if ($orderTotal > $availableCredit) {
            throw new Exception(
                "Order total exceeds available credit. Available: " .
                LeyscoHelpers::formatCurrency($availableCredit)
            );
        }
    }

    /**
     * Validate product availability at warehouse
     * 
     * @param Product $product
     * @param int $warehouseId
     * @param int $quantity
     * @throws Exception
     */
    private function validateProductAvailability(Product $product, int $warehouseId, int $quantity): void 
    {
        if (!$product->isAvailableAtWarehouse($warehouseId, $quantity)) {
            throw new Exception(
                "Insufficient stock for {$product->name} at the selected warehouse"
            );
        }
    }

    /**
     * Calculate line item pricing with discounts and tax
     * 
     * @param array $itemData
     * @param Product $product
     * @return array
     */
    private function calculateLineItem(array $itemData, Product $product): array
    {
        $unitPrice = $itemData['unit_price'] ?? $product->price;
        $quantity = $itemData['quantity'];
        
        // Calculate line subtotal
        $lineSubtotal = $unitPrice * $quantity;
        
        // Calculate line discount
        $discountAmount = 0;
        if (isset($itemData['discount_type'])) {
            $discountAmount = LeyscoHelpers::calculateDiscount(
                $lineSubtotal,
                $itemData['discount_type'],
                $itemData['discount_value']
            );
        }
        
        // Subtotal after discount
        $subtotal = $lineSubtotal - $discountAmount;
        
        // Calculate tax on discounted amount
        $taxAmount = LeyscoHelpers::calculateTax($subtotal, $product->tax_rate);
        
        // Total
        $total = $subtotal + $taxAmount;
        
        return [
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }
}