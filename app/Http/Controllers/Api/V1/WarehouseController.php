<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Http\Resources\InventoryResource;
use App\Http\Resources\StockTransferResource;
use App\Models\Warehouse;
use App\Models\StockTransfer;
use App\Models\Product;
use App\Services\LeysInventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function __construct(
        private LeysInventoryService $inventoryService
    ) {}

    /**
     * Display a listing of warehouses
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
                  ->orWhere('address', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $warehouses = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Warehouses retrieved successfully',
            'data' => WarehouseResource::collection($warehouses),
            'meta' => [
                'total' => $warehouses->count(),
                'by_type' => [
                    'Main' => $warehouses->where('type', 'Main')->count(),
                    'Regional' => $warehouses->where('type', 'Regional')->count(),
                    'Branch' => $warehouses->where('type', 'Branch')->count(),
                ]
            ]
        ]);
    }

    /**
     * Get warehouse-specific inventory
     */
    public function inventory(int $id, Request $request): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        
        $query = $warehouse->inventory()->with(['product.category']);

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'low':
                    $query->whereRaw('available_quantity <= (SELECT reorder_level FROM products WHERE products.id = inventory.product_id)');
                    break;
                case 'out':
                    $query->where('available_quantity', '<=', 0);
                    break;
                case 'available':
                    $query->where('available_quantity', '>', 0);
                    break;
            }
        }

        // Search products
        if ($request->has('search')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'product_id');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 20);
        $inventory = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse inventory retrieved successfully',
            'data' => InventoryResource::collection($inventory),
            'meta' => [
                'warehouse' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'type' => $warehouse->type,
                ],
                'totals' => [
                    'total_products' => $warehouse->inventory()->count(),
                    'total_quantity' => $warehouse->inventory()->sum('quantity'),
                    'total_available' => $warehouse->inventory()->sum('available_quantity'),
                    'total_reserved' => $warehouse->inventory()->sum('reserved_quantity'),
                    'low_stock_items' => $warehouse->inventory()
                        ->whereRaw('available_quantity <= (SELECT reorder_level FROM products WHERE products.id = inventorys.product_id)')
                        ->count(),
                ],
                'pagination' => [
                    'current_page' => $inventory->currentPage(),
                    'from' => $inventory->firstItem(),
                    'last_page' => $inventory->lastPage(),
                    'per_page' => $inventory->perPage(),
                    'to' => $inventory->lastItem(),
                    'total' => $inventory->total(),
                ]
            ]
        ]);
    }

    /**
     * Transfer stock between warehouses
     */
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Create transfer record
            $transfer = StockTransfer::create([
                'product_id' => $request->product_id,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'requested_by' => Auth::id(),
                'quantity' => $request->quantity,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Execute the transfer
            $this->inventoryService->transferStock(
                $request->product_id,
                $request->from_warehouse_id,
                $request->to_warehouse_id,
                $request->quantity
            );

            // Update transfer status
            $transfer->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock transferred successfully',
                'data' => new StockTransferResource($transfer->load([
                    'product',
                    'fromWarehouse',
                    'toWarehouse',
                    'requestedBy'
                ]))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to transfer stock',
                'errors' => ['transfer' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Get stock transfer history
     */
    public function transferHistory(Request $request): JsonResponse
    {
        $query = StockTransfer::with([
            'product',
            'fromWarehouse',
            'toWarehouse',
            'requestedBy'
        ]);

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->where(function($q) use ($request) {
                $q->where('from_warehouse_id', $request->warehouse_id)
                  ->orWhere('to_warehouse_id', $request->warehouse_id);
            });
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $transfers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Transfer history retrieved successfully',
            'data' => StockTransferResource::collection($transfers),
            'meta' => [
                'pagination' => [
                    'current_page' => $transfers->currentPage(),
                    'from' => $transfers->firstItem(),
                    'last_page' => $transfers->lastPage(),
                    'per_page' => $transfers->perPage(),
                    'to' => $transfers->lastItem(),
                    'total' => $transfers->total(),
                ]
            ]
        ]);
    }

    /**
     * Get warehouse statistics
     */
    public function statistics(int $id): JsonResponse
    {
        $warehouse = Warehouse::with(['inventory.product'])->findOrFail($id);

        $stats = [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'code' => $warehouse->code,
                'type' => $warehouse->type,
                'capacity' => $warehouse->capacity,
            ],
            'inventory' => [
                'total_products' => $warehouse->inventory()->count(),
                'total_quantity' => $warehouse->inventory()->sum('quantity'),
                'total_available' => $warehouse->inventory()->sum('available_quantity'),
                'total_reserved' => $warehouse->inventory()->sum('reserved_quantity'),
                'capacity_used_percentage' => $warehouse->capacity > 0 
                    ? round(($warehouse->inventory()->sum('quantity') / $warehouse->capacity) * 100, 2)
                    : 0,
            ],
            'stock_status' => [
                'in_stock' => $warehouse->inventory()->where('available_quantity', '>', 0)->count(),
                'out_of_stock' => $warehouse->inventory()->where('available_quantity', '<=', 0)->count(),
                'low_stock' => $warehouse->inventory()
                    ->whereRaw('available_quantity <= (SELECT reorder_level FROM products WHERE products.id = inventorys.product_id)')
                    ->where('available_quantity', '>', 0)
                    ->count(),
            ],
            'recent_transfers' => [
                'incoming_count' => StockTransfer::where('to_warehouse_id', $id)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
                'outgoing_count' => StockTransfer::where('from_warehouse_id', $id)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Warehouse statistics retrieved successfully',
            'data' => $stats
        ]);
    }
}