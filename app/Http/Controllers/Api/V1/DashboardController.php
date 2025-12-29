<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Inventory;
use App\Helpers\LeyscoHelpers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get overall dashboard summary
     */
    public function summary(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $cacheKey = LeyscoHelpers::generateCacheKey('dashboard:summary', ['period' => $period]);
        
        $data = Cache::remember($cacheKey, config('leys_config.cache.dashboard_ttl', 300), function () use ($period) {
            $dateRange = LeyscoHelpers::getDateRange($period);
            
            // Sales metrics
            $orders = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            $totalSales = $orders->sum('total_amount');
            $totalOrders = $orders->count();
            $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
            
            // Compare with previous period
            $previousStart = $dateRange['start']->copy()->sub($dateRange['start']->diff($dateRange['end']));
            $previousOrders = Order::whereBetween('created_at', [$previousStart, $dateRange['start']]);
            $previousSales = $previousOrders->sum('total_amount');
            
            $salesGrowth = $previousSales > 0 
                ? (($totalSales - $previousSales) / $previousSales) * 100 
                : 0;
            
            // Inventory metrics
            $totalProducts = Product::count();
            $totalInventory = Inventory::sum('quantity');
            $availableInventory = Inventory::sum('available_quantity');
            $reservedInventory = Inventory::sum('reserved_quantity');
            
            // Low stock count
            $lowStockCount = Product::whereHas('inventory', function($q) {
                $q->whereRaw('available_quantity <= products.reorder_level');
            })->count();
            
            // Customer metrics
            $totalCustomers = Customer::count();
            $activeCustomers = Customer::whereHas('orders', function($q) use ($dateRange) {
                $q->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            })->count();
            
            return [
                'period' => $period,
                'date_range' => [
                    'start' => $dateRange['start']->toIso8601String(),
                    'end' => $dateRange['end']->toIso8601String(),
                ],
                'sales' => [
                    'total_amount' => $totalSales,
                    'total_amount_formatted' => LeyscoHelpers::formatCurrency($totalSales),
                    'total_orders' => $totalOrders,
                    'average_order_value' => round($avgOrderValue, 2),
                    'average_order_value_formatted' => LeyscoHelpers::formatCurrency($avgOrderValue),
                    'growth_percentage' => round($salesGrowth, 2),
                ],
                'inventory' => [
                    'total_products' => $totalProducts,
                    'total_quantity' => $totalInventory,
                    'available_quantity' => $availableInventory,
                    'reserved_quantity' => $reservedInventory,
                    'low_stock_count' => $lowStockCount,
                    'stock_availability_percentage' => $totalInventory > 0 
                        ? round(($availableInventory / $totalInventory) * 100, 2)
                        : 0,
                ],
                'customers' => [
                    'total' => $totalCustomers,
                    'active_in_period' => $activeCustomers,
                    'activity_rate' => $totalCustomers > 0 
                        ? round(($activeCustomers / $totalCustomers) * 100, 2)
                        : 0,
                ],
                'order_status' => [
                    'pending' => Order::where('status', 'pending')->count(),
                    'confirmed' => Order::where('status', 'confirmed')->count(),
                    'processing' => Order::where('status', 'processing')->count(),
                    'shipped' => Order::where('status', 'shipped')->count(),
                    'delivered' => Order::where('status', 'delivered')->count(),
                    'cancelled' => Order::where('status', 'cancelled')->count(),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary retrieved successfully',
            'data' => $data
        ]);
    }

    /**
     * Get sales performance data
     */
    public function salesPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $period = $request->get('period', 'month');
        $dateRange = $request->has('start_date') && $request->has('end_date')
            ? ['start' => $request->start_date, 'end' => $request->end_date]
            : LeyscoHelpers::getDateRange($period);

        $cacheKey = LeyscoHelpers::generateCacheKey('dashboard:sales', [
            'start' => $dateRange['start'],
            'end' => $dateRange['end']
        ]);

        $data = Cache::remember($cacheKey, 300, function () use ($dateRange) {
            // Daily sales breakdown
            $dailySales = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as total_sales'),
                    DB::raw('AVG(total_amount) as avg_order_value')
                )
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'order_count' => $item->order_count,
                        'total_sales' => $item->total_sales,
                        'total_sales_formatted' => LeyscoHelpers::formatCurrency($item->total_sales),
                        'avg_order_value' => round($item->avg_order_value, 2),
                        'avg_order_value_formatted' => LeyscoHelpers::formatCurrency($item->avg_order_value),
                    ];
                });

            // Top performing sales reps
            $topSalesReps = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    'created_by',
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(total_amount) as total_sales')
                )
                ->with('createdBy:id,username,first_name,last_name')
                ->groupBy('created_by')
                ->orderBy('total_sales', 'desc')
                ->take(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->created_by,
                        'username' => $item->createdBy->username ?? 'Unknown',
                        'full_name' => $item->createdBy->full_name ?? 'Unknown',
                        'order_count' => $item->order_count,
                        'total_sales' => $item->total_sales,
                        'total_sales_formatted' => LeyscoHelpers::formatCurrency($item->total_sales),
                    ];
                });

            return [
                'daily_sales' => $dailySales,
                'top_sales_reps' => $topSalesReps,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Sales performance retrieved successfully',
            'data' => $data
        ]);
    }

    /**
     * Get inventory status by category
     */
    public function inventoryStatus(): JsonResponse
    {
        $cacheKey = 'dashboard:inventorys:status';

        $data = Cache::remember($cacheKey, 600, function () {
            $categories = DB::table('categories')
                ->join('products', 'categories.id', '=', 'products.category_id')
                ->join('inventorys', 'products.id', '=', 'inventorys.product_id')
                ->select(
                    'categories.id',
                    'categories.name',
                    DB::raw('COUNT(DISTINCT products.id) as product_count'),
                    DB::raw('SUM(inventorys.quantity) as total_quantity'),
                    DB::raw('SUM(inventorys.available_quantity) as available_quantity'),
                    DB::raw('SUM(inventorys.reserved_quantity) as reserved_quantity')
                )
                ->groupBy('categories.id', 'categories.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'category_id' => $item->id,
                        'category_name' => $item->name,
                        'product_count' => $item->product_count,
                        'total_quantity' => $item->total_quantity,
                        'available_quantity' => $item->available_quantity,
                        'reserved_quantity' => $item->reserved_quantity,
                        'availability_percentage' => $item->total_quantity > 0 
                            ? round(($item->available_quantity / $item->total_quantity) * 100, 2)
                            : 0,
                    ];
                });

            return $categories;
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventory status retrieved successfully',
            'data' => $data
        ]);
    }

    /**
     * Get top selling products
     */
    public function topProducts(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 5);
        $period = $request->get('period', 'month');
        $dateRange = LeyscoHelpers::getDateRange($period);

        $cacheKey = LeyscoHelpers::generateCacheKey('dashboard:top-products', [
            'limit' => $limit,
            'period' => $period
        ]);

        $data = Cache::remember($cacheKey, 300, function () use ($limit, $dateRange) {
            $topProducts = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->whereBetween('orders.created_at', [$dateRange['start'], $dateRange['end']])
                ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
                ->select(
                    'products.id',
                    'products.sku',
                    'products.name',
                    'categories.name as category',
                    DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
                    DB::raw('SUM(order_items.total) as total_revenue'),
                    DB::raw('COUNT(DISTINCT orders.id) as order_count')
                )
                ->groupBy('products.id', 'products.sku', 'products.name', 'categories.name')
                ->orderBy('total_revenue', 'desc')
                ->take($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'product_id' => $item->id,
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'category' => $item->category,
                        'quantity_sold' => $item->total_quantity_sold,
                        'total_revenue' => $item->total_revenue,
                        'total_revenue_formatted' => LeyscoHelpers::formatCurrency($item->total_revenue),
                        'order_count' => $item->order_count,
                    ];
                });

            return $topProducts;
        });

        return response()->json([
            'success' => true,
            'message' => 'Top products retrieved successfully',
            'data' => $data
        ]);
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(): JsonResponse
    {
        Cache::tags(['dashboard'])->flush();
        
        // Clear specific dashboard keys
        $keys = [
            'dashboard:summary:*',
            'dashboard:sales:*',
            'dashboard:inventory:status',
            'dashboard:top-products:*',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard cache cleared successfully',
            'data' => null
        ]);
    }
}