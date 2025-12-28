<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\CustomerMapDataResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Helpers\LeyscoHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by category (A, A+, B, C)
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by type (Garage, Dealership, etc.)
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by territory
        if ($request->has('territory')) {
            $query->where('territory', $request->territory);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $customers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Customers retrieved successfully',
            'data' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'from' => $customers->firstItem(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'to' => $customers->lastItem(),
                'total' => $customers->total(),
            ],
            'links' => [
                'first' => $customers->url(1),
                'last' => $customers->url($customers->lastPage()),
                'prev' => $customers->previousPageUrl(),
                'next' => $customers->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Display the specified customer
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Customer retrieved successfully',
            'data' => new CustomerResource($customer)
        ]);
    }

    /**
     * Store a newly created customer
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => new CustomerResource($customer)
        ], 201);
    }

    /**
     * Update the specified customer
     */
    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => new CustomerResource($customer->fresh())
        ]);
    }

    /**
     * Remove the specified customer (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        
        // Check if customer has orders
        if ($customer->orders()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete customer with existing orders',
                'errors' => ['customer' => ['This customer has orders and cannot be deleted']]
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
            'data' => null
        ]);
    }

    /**
     * Get customer order history
     */
    public function orders(int $id, Request $request): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        
        $orders = $customer->orders()
            ->with(['createdBy', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Customer orders retrieved successfully',
            'data' => OrderResource::collection($orders),
            'meta' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'category' => $customer->category,
                ],
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'from' => $orders->firstItem(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'to' => $orders->lastItem(),
                    'total' => $orders->total(),
                ]
            ]
        ]);
    }

    /**
     * Get customer credit status
     */
    public function creditStatus(int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);
        
        $availableCredit = LeyscoHelpers::calculateAvailableCredit(
            $customer->credit_limit,
            $customer->current_balance
        );

        $creditUsagePercentage = $customer->credit_limit > 0 
            ? round(($customer->current_balance / $customer->credit_limit) * 100, 2) 
            : 0;

        // Determine credit status
        $creditStatus = 'good';
        if ($creditUsagePercentage >= 90) {
            $creditStatus = 'critical';
        } elseif ($creditUsagePercentage >= 75) {
            $creditStatus = 'warning';
        } elseif ($creditUsagePercentage >= 50) {
            $creditStatus = 'moderate';
        }

        return response()->json([
            'success' => true,
            'message' => 'Credit status retrieved successfully',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'category' => $customer->category,
                ],
                'credit_limit' => $customer->credit_limit,
                'current_balance' => $customer->current_balance,
                'available_credit' => $availableCredit,
                'credit_usage_percentage' => $creditUsagePercentage,
                'credit_status' => $creditStatus,
                'payment_terms' => $customer->payment_terms,
                'payment_terms_label' => $customer->payment_terms . ' days',
                'formatted' => [
                    'credit_limit' => LeyscoHelpers::formatCurrency($customer->credit_limit),
                    'current_balance' => LeyscoHelpers::formatCurrency($customer->current_balance),
                    'available_credit' => LeyscoHelpers::formatCurrency($availableCredit),
                ],
                'recent_orders_count' => $customer->orders()->count(),
                'pending_orders_value' => $customer->orders()
                    ->whereIn('status', ['pending', 'confirmed', 'processing'])
                    ->sum('total_amount'),
            ]
        ]);
    }

    /**
     * Get customers map data for geographic visualization
     */
    public function mapData(Request $request): JsonResponse
    {
        $query = Customer::whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Filter by category if provided
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Filter by type if provided
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $customers = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Customer map data retrieved successfully',
            'data' => CustomerMapDataResource::collection($customers),
            'meta' => [
                'total_customers' => $customers->count(),
                'categories' => [
                    'A+' => $customers->where('category', 'A+')->count(),
                    'A' => $customers->where('category', 'A')->count(),
                    'B' => $customers->where('category', 'B')->count(),
                    'C' => $customers->where('category', 'C')->count(),
                ],
                'types' => [
                    'Garage' => $customers->where('type', 'Garage')->count(),
                    'Dealership' => $customers->where('type', 'Dealership')->count(),
                    'Distributor' => $customers->where('type', 'Distributor')->count(),
                    'Retailer' => $customers->where('type', 'Retailer')->count(),
                ]
            ]
        ]);
    }

    /**
     * Get customer statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = [
            'total_customers' => Customer::count(),
            'by_category' => [
                'A+' => Customer::where('category', 'A+')->count(),
                'A' => Customer::where('category', 'A')->count(),
                'B' => Customer::where('category', 'B')->count(),
                'C' => Customer::where('category', 'C')->count(),
            ],
            'by_type' => [
                'Garage' => Customer::where('type', 'Garage')->count(),
                'Dealership' => Customer::where('type', 'Dealership')->count(),
                'Distributor' => Customer::where('type', 'Distributor')->count(),
                'Retailer' => Customer::where('type', 'Retailer')->count(),
            ],
            'credit' => [
                'total_credit_limit' => Customer::sum('credit_limit'),
                'total_outstanding' => Customer::sum('current_balance'),
                'total_available' => Customer::sum('credit_limit') - Customer::sum('current_balance'),
                'formatted' => [
                    'total_credit_limit' => LeyscoHelpers::formatCurrency(Customer::sum('credit_limit')),
                    'total_outstanding' => LeyscoHelpers::formatCurrency(Customer::sum('current_balance')),
                    'total_available' => LeyscoHelpers::formatCurrency(
                        Customer::sum('credit_limit') - Customer::sum('current_balance')
                    ),
                ]
            ],
            'top_customers' => Customer::orderBy('current_balance', 'desc')
                ->take(5)
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'category' => $customer->category,
                        'current_balance' => $customer->current_balance,
                        'formatted_balance' => LeyscoHelpers::formatCurrency($customer->current_balance),
                    ];
                }),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Customer statistics retrieved successfully',
            'data' => $stats
        ]);
    }
}

