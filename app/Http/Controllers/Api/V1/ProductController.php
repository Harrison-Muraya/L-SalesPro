<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\LeysInventoryService;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
     public function __construct(
        private LeysInventoryService $inventoryService
    ) {}

    /**
     * Display a listing of products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'inventory.warehouse']);

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->byCategory($request->category_id);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => new ProductCollection($products)
        ]);
    }

    /**
     * Display the specified product
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category', 'inventory.warehouse'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully',
            'data' => new ProductResource($product)
        ]);
    }

    /**
     * Store a newly created product
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => new ProductResource($product->load('category'))
        ], 201);
    }

    /**
     * Update the specified product
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product->load('category'))
        ]);
    }

    /**
     * Remove the specified product
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
            'data' => null
        ]);
    }

    /**
     * Get product stock information
     */
    public function stock(int $id): JsonResponse
    {
        $stockInfo = $this->inventoryService->getProductStock($id);

        return response()->json([
            'success' => true,
            'message' => 'Product stock retrieved successfully',
            'data' => $stockInfo
        ]);
    }

    /**
     * Reserve stock for a product
     */
    public function reserveStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $reservation = $this->inventoryService->reserveStock(
                $id,
                $request->warehouse_id,
                $request->quantity
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock reserved successfully',
                'data' => [
                    'reservation_reference' => $reservation->reservation_reference,
                    'expires_at' => $reservation->expires_at->toIso8601String(),
                    'quantity' => $reservation->quantity,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['stock' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Release stock reservation
     */
    public function releaseStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reservation_reference' => 'required|string|exists:stock_reservations,reservation_reference',
        ]);

        try {
            $this->inventoryService->releaseReservation($request->reservation_reference);

            return response()->json([
                'success' => true,
                'message' => 'Stock reservation released successfully',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['reservation' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Get low stock products
     */
    public function lowStock(): JsonResponse
    {
        $lowStockProducts = $this->inventoryService->getLowStockProducts();

        return response()->json([
            'success' => true,
            'message' => 'Low stock products retrieved successfully',
            'data' => $lowStockProducts
        ]);
    }
}
