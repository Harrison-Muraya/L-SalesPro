<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\LeysOrderService;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
     public function __construct(
        private LeysOrderService $orderService
    ) {}

    /**
     * Display a listing of orders
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['customer', 'createdBy', 'items']);

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->byCustomer($request->customer_id);
        }

        // Date range filter
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Only show user's own orders if not a manager
        if (!$request->user()->isManager()) {
            $query->where('created_by', $request->user()->id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => new OrderCollection($orders)
        ]);
    }

    /**
     * Display the specified order
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with([
            'customer',
            'createdBy',
            'items.product.category',
            'items.warehouse'
        ])->findOrFail($id);

        // Check authorization
        if (!auth()->user()->isManager() && $order->created_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this order',
                'errors' => ['authorization' => ['Insufficient permissions']]
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data' => new OrderResource($order)
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'errors' => ['order' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $this->orderService->updateOrderStatus(
                $order,
                $request->status
            );

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => new OrderResource($updatedOrder->load([
                    'customer',
                    'createdBy',
                    'items.product',
                    'items.warehouse'
                ]))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['status' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Calculate order total without creating order
     */
    public function calculateTotal(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.warehouse_id' => 'required|exists:warehouses,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $calculation = $this->orderService->calculateOrderTotal($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Order total calculated successfully',
                'data' => $calculation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate order total',
                'errors' => ['calculation' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Generate invoice for order
     */
    public function invoice(int $id): JsonResponse
    {
        $order = Order::with([
            'customer',
            'createdBy',
            'items.product',
            'items.warehouse'
        ])->findOrFail($id);

        $invoice = $this->orderService->generateInvoice($order);

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully',
            'data' => $invoice
        ]);
    }
}
