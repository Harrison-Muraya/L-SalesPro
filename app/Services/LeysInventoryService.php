<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Inventory;
use App\Helpers\LeyscoHelpers;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeysInventoryService
{
    /**
     * Reserve stock for an order
     * 
     * @param int $productId
     * @param int $warehouseId
     * @param int $quantity
     * @param int|null $orderId
     * @return StockReservation
     * @throws Exception
     */
    public function reserveStock(
        int $productId,
        int $warehouseId,
        int $quantity,
        ?int $orderId = null
    ): StockReservation {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $orderId) {
            // Get inventory with lock
            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new Exception("Inventory record not found");
            }

            if ($inventory->available_quantity < $quantity) {
                throw new Exception(
                    "Insufficient stock. Available: {$inventory->available_quantity}, Requested: {$quantity}"
                );
            }

            // Update inventory
            $inventory->reserved_quantity += $quantity;
            $inventory->available_quantity -= $quantity;
            $inventory->save();

            // Create reservation
            $timeoutMinutes = config('leys_config.stock_reservation.timeout_minutes', 30);
            
            $reservation = StockReservation::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'order_id' => $orderId,
                'reservation_reference' => LeyscoHelpers::generateReservationReference(),
                'quantity' => $quantity,
                'status' => 'pending',
                'expires_at' => Carbon::now()->addMinutes($timeoutMinutes),
            ]);

            return $reservation;
        });
    }

    /**
     * Release stock reservation
     * 
     * @param string $reservationReference
     * @return bool
     * @throws Exception
     */
    public function releaseReservation(string $reservationReference): bool
    {
        return DB::transaction(function () use ($reservationReference) {
            $reservation = StockReservation::where('reservation_reference', $reservationReference)
                ->lockForUpdate()
                ->first();

            if (!$reservation) {
                throw new Exception("Reservation not found");
            }

            if ($reservation->status !== 'pending') {
                throw new Exception("Reservation already {$reservation->status}");
            }

            // Update inventory
            $inventory = Inventory::where('product_id', $reservation->product_id)
                ->where('warehouse_id', $reservation->warehouse_id)
                ->lockForUpdate()
                ->first();

            $inventory->reserved_quantity -= $reservation->quantity;
            $inventory->available_quantity += $reservation->quantity;
            $inventory->save();

            // Update reservation
            $reservation->status = 'released';
            $reservation->save();

            return true;
        });
    }

    /**
     * Confirm stock reservations for an order
     * 
     * @param int $orderId
     * @return bool
     */
    public function confirmReservations(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $reservations = StockReservation::where('order_id', $orderId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                // Deduct from reserved and total quantity
                $inventory = Inventory::where('product_id', $reservation->product_id)
                    ->where('warehouse_id', $reservation->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                $inventory->reserved_quantity -= $reservation->quantity;
                $inventory->quantity -= $reservation->quantity;
                $inventory->save();

                // Update reservation
                $reservation->status = 'confirmed';
                $reservation->save();
            }

            return true;
        });
    }

    /**
     * Release all reservations for an order
     * 
     * @param int $orderId
     * @return bool
     */
    public function releaseOrderReservations(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $reservations = StockReservation::where('order_id', $orderId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $inventory = Inventory::where('product_id', $reservation->product_id)
                    ->where('warehouse_id', $reservation->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                $inventory->reserved_quantity -= $reservation->quantity;
                $inventory->available_quantity += $reservation->quantity;
                $inventory->save();

                $reservation->status = 'released';
                $reservation->save();
            }

            return true;
        });
    }

    /**
     * Process expired reservations
     * This should be called by a scheduled command
     * 
     * @return int Number of expired reservations processed
     */
    public function processExpiredReservations(): int
    {
        $expiredReservations = StockReservation::where('status', 'pending')
            ->where('expires_at', '<', Carbon::now())
            ->get();

        $count = 0;

        foreach ($expiredReservations as $reservation) {
            try {
                DB::transaction(function () use ($reservation) {
                    $inventory = Inventory::where('product_id', $reservation->product_id)
                        ->where('warehouse_id', $reservation->warehouse_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory) {
                        $inventory->reserved_quantity -= $reservation->quantity;
                        $inventory->available_quantity += $reservation->quantity;
                        $inventory->save();
                    }

                    $reservation->status = 'expired';
                    $reservation->save();
                });

                $count++;
            } catch (Exception $e) {
                Log::error("Failed to process expired reservation {$reservation->id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Transfer stock between warehouses
     * 
     * @param int $productId
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @param int $quantity
     * @return bool
     * @throws Exception
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity
    ): bool {
        return DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity) {
            // Lock source inventory
            $fromInventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $fromWarehouseId)
                ->lockForUpdate()
                ->first();

            if (!$fromInventory || $fromInventory->available_quantity < $quantity) {
                throw new Exception("Insufficient stock at source warehouse");
            }

            // Lock destination inventory
            $toInventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $toWarehouseId)
                ->lockForUpdate()
                ->first();

            if (!$toInventory) {
                throw new Exception("Destination warehouse inventory not found");
            }

            // Perform transfer
            $fromInventory->quantity -= $quantity;
            $fromInventory->available_quantity -= $quantity;
            $fromInventory->save();

            $toInventory->quantity += $quantity;
            $toInventory->available_quantity += $quantity;
            $toInventory->save();

            return true;
        });
    }

    /**
     * Get low stock products across all warehouses
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getLowStockProducts()
    {
        return Product::with(['category', 'inventory.warehouse'])
            ->whereHas('inventory', function ($query) {
                $query->whereRaw('available_quantity <= products.reorder_level');
            })
            ->get()
            ->map(function ($product) {
                return [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category->name,
                    'reorder_level' => $product->reorder_level,
                    'warehouses' => $product->inventory->map(function ($inv) {
                        return [
                            'warehouse_id' => $inv->warehouse_id,
                            'warehouse_name' => $inv->warehouse->name,
                            'available_quantity' => $inv->available_quantity,
                            'status' => $inv->available_quantity <= 0 ? 'out_of_stock' : 'low_stock',
                        ];
                    })->filter(function ($inv) use ($product) {
                        return $inv['available_quantity'] <= $product->reorder_level;
                    })->values(),
                ];
            });
    }

    /**
     * Get real-time stock for a product across all warehouses
     * 
     * @param int $productId
     * @return array
     */
    public function getProductStock(int $productId): array
    {
        $product = Product::with(['inventory.warehouse'])->findOrFail($productId);

        $warehouses = $product->inventory->map(function ($inv) {
            return [
                'warehouse_id' => $inv->warehouse_id,
                'warehouse_name' => $inv->warehouse->name,
                'warehouse_code' => $inv->warehouse->code,
                'quantity' => $inv->quantity,
                'reserved_quantity' => $inv->reserved_quantity,
                'available_quantity' => $inv->available_quantity,
                'last_restock_date' => $inv->last_restock_date,
            ];
        });

        return [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'reorder_level' => $product->reorder_level,
            ],
            'total_quantity' => $warehouses->sum('quantity'),
            'total_reserved' => $warehouses->sum('reserved_quantity'),
            'total_available' => $warehouses->sum('available_quantity'),
            'warehouses' => $warehouses,
            'is_low_stock' => $product->isLowStock(),
        ];
    }

    /**
     * Restock inventory
     * 
     * @param int $productId
     * @param int $warehouseId
     * @param int $quantity
     * @return Inventory
     */
    public function restock(int $productId, int $warehouseId, int $quantity): Inventory
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity) {
            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                throw new Exception("Inventory record not found");
            }

            $inventory->quantity += $quantity;
            $inventory->available_quantity += $quantity;
            $inventory->last_restock_date = Carbon::now();
            $inventory->save();

            return $inventory;
        });
    }
}