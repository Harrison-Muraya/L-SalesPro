<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmationMail;
use App\Mail\LowStockAlertMail;
use App\Mail\CreditLimitWarningMail;
use App\Mail\SystemAnnouncementMail;

class LeysNotificationService
{
    /**
     * Get user notifications with pagination
     */
    public function getUserNotifications(
        int $userId,
        int $perPage = 15,
        ?string $type = null
    ): LengthAwarePaginator {
        $query = Notification::where('user_id', $userId);

        if ($type) {
            $query->byType($type);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->count();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId, int $userId): Notification
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();
        
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return $notification->fresh();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(string $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();
        
        return $notification->delete();
    }

    /**
     * Create notification
     */
    private function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Send order confirmation notification
     */
    public function sendOrderConfirmation(Order $order): void
    {
        // Get the user associated with this order (assuming customer has a user relationship)
        $user = $order->customer->user ?? null;

        if (!$user) {
            return;
        }

        // Create database notification
        $this->createNotification(
            $user->id,
            'order_confirmation',
            'Order Confirmed',
            "Your order {$order->order_number} has been confirmed successfully.",
            [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
                'action_url' => "/orders/{$order->id}",
                'priority' => 'normal',
            ]
        );

        // Queue email notification
        dispatch(function () use ($user, $order) {
            Mail::to($user->email)->send(new OrderConfirmationMail($order));
        })->onQueue('notifications');
    }

    /**
     * Send low stock alert
     */
    public function sendLowStockAlert(Product $product): void
    {
        // Get all Sales Managers
        $managers = User::where('role', 'Sales Manager')
            ->where('status', 'active')
            ->get();

        $totalStock = $product->inventory->sum('quantity');

        foreach ($managers as $manager) {
            // Create database notification
            $this->createNotification(
                $manager->id,
                'low_stock_alert',
                'Low Stock Alert',
                "Product {$product->name} is running low on stock. Current: {$totalStock}, Reorder Level: {$product->reorder_level}",
                [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => $totalStock,
                    'reorder_level' => $product->reorder_level,
                    'action_url' => "/products/{$product->id}",
                    'priority' => 'high',
                ]
            );

            // Queue email notification
            dispatch(function () use ($manager, $product) {
                Mail::to($manager->email)->send(new LowStockAlertMail($product));
            })->onQueue('notifications');
        }
    }

    /**
     * Send credit limit warning
     */
    public function sendCreditLimitWarning(Customer $customer): void
    {
        $user = $customer->user ?? null;

        if (!$user) {
            return;
        }

        $availableCredit = $customer->credit_limit - $customer->current_balance;
        $utilizationPercentage = ($customer->current_balance / $customer->credit_limit) * 100;

        // Create database notification
        $this->createNotification(
            $user->id,
            'credit_limit_warning',
            'Credit Limit Warning',
            "Customer {$customer->name} has utilized " . round($utilizationPercentage, 2) . "% of their credit limit.",
            [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'credit_limit' => $customer->credit_limit,
                'current_balance' => $customer->current_balance,
                'available_credit' => $availableCredit,
                'utilization_percentage' => $utilizationPercentage,
                'action_url' => "/customers/{$customer->id}",
                'priority' => 'high',
            ]
        );

        // Queue email notification
        dispatch(function () use ($user, $customer) {
            Mail::to($user->email)->send(new CreditLimitWarningMail($customer));
        })->onQueue('notifications');
    }

    /**
     * Send system announcement
     */
    public function sendSystemAnnouncement(
        string $title,
        string $message,
        array $data = []
    ): void {
        $users = User::where('status', 'active')->get();

        foreach ($users as $user) {
            // Create database notification
            $this->createNotification(
                $user->id,
                'system_announcement',
                $title,
                $message,
                array_merge([
                    'priority' => 'normal',
                ], $data)
            );

            // Queue email notification if needed
            if ($data['send_email'] ?? false) {
                dispatch(function () use ($user, $title, $message, $data) {
                    Mail::to($user->email)->send(
                        new SystemAnnouncementMail($title, $message, $data)
                    );
                })->onQueue('notifications');
            }
        }
    }

    /**
     * Bulk create notifications
     */
    public function bulkCreateNotifications(array $notifications): void
    {
        DB::transaction(function () use ($notifications) {
            foreach ($notifications as $notification) {
                Notification::create($notification);
            }
        });
    }

    /**
     * Delete old read notifications
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        return Notification::where('is_read', true)
            ->where('read_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(int $userId): array
    {
        return [
            'total' => Notification::where('user_id', $userId)->count(),
            'unread' => Notification::where('user_id', $userId)->unread()->count(),
            'by_type' => Notification::where('user_id', $userId)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }
}