<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeyscoHelpers
{
    /**
     * Format currency in Kenyan Shillings
     * 
     * @param float $amount
     * @return string
     */
    // public static function formatCurrency(float $amount): string
    // {
    //     $symbol = config('leys_config.currency_symbol', 'KES');
    //     $formatted = number_format($amount, 2, '.', ',');
        
    //     return "{$symbol} {$formatted} /=";
    // }

    public static function formatCurrency(?float $amount): string
    {
        $symbol = config('leys_config.currency_symbol', 'KES');
        $amount = $amount ?? 0.0; // default to 0 if null
        $formatted = number_format($amount, 2, '.', ',');
        return "{$symbol} {$formatted} /=";
    }

    /**
     * Generate order number in format: ORD-YYYY-MM-XXX
     * 
     * @return string
     */
    public static function generateOrderNumber(): string
    {
        $prefix = config('leys_config.order_prefix', 'ORD');
        $date = Carbon::now();
        $year = $date->format('Y');
        $month = $date->format('m');
        
        // Get count of orders for current month
        $count = DB::table('orders')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count();
        
        $sequence = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$year}-{$month}-{$sequence}";
    }

    /**
     * Calculate tax amount
     * 
     * @param float $amount
     * @param float $rate
     * @return float
     */
    public static function calculateTax(float $amount, float $rate): float
    {
        return round(($amount * $rate) / 100, 2);
    }

    /**
     * Calculate discount amount
     * 
     * @param float $amount
     * @param string $type (percentage|fixed)
     * @param float $value
     * @return float
     */
    public static function calculateDiscount(float $amount, string $type, float $value): float
    {
        if ($type === 'percentage') {
            return round(($amount * $value) / 100, 2);
        }
        
        return min($value, $amount); // Fixed discount cannot exceed amount
    }

    /**
     * Calculate available credit for customer
     * 
     * @param float $creditLimit
     * @param float $currentBalance
     * @return float
     */
    public static function calculateAvailableCredit(float $creditLimit, float $currentBalance): float
    {
        return max(0, $creditLimit - $currentBalance);
    }

    /**
     * Generate reservation reference
     * 
     * @return string
     */
    public static function generateReservationReference(): string
    {
        return 'RSV-' . strtoupper(uniqid()) . '-' . time();
    }

    /**
     * Format phone number to international format
     * 
     * @param string $phone
     * @return string
     */
    public static function formatPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with +254
        if (substr($phone, 0, 1) === '0') {
            $phone = '+254' . substr($phone, 1);
        }
        
        // If starts with 254, add +
        if (substr($phone, 0, 3) === '254') {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }

    /**
     * Calculate inventory turnover rate
     * 
     * @param float $costOfGoodsSold
     * @param float $averageInventory
     * @return float
     */
    public static function calculateInventoryTurnover(float $costOfGoodsSold, float $averageInventory): float
    {
        if ($averageInventory == 0) {
            return 0;
        }
        
        return round($costOfGoodsSold / $averageInventory, 2);
    }

    /**
     * Get date range for filtering
     * 
     * @param string $period (today|week|month|quarter|year)
     * @return array
     */
    public static function getDateRange(string $period): array
    {
        $end = Carbon::now()->endOfDay();
        
        switch ($period) {
            case 'today':
                $start = Carbon::now()->startOfDay();
                break;
            case 'week':
                $start = Carbon::now()->startOfWeek();
                break;
            case 'month':
                $start = Carbon::now()->startOfMonth();
                break;
            case 'quarter':
                $start = Carbon::now()->startOfQuarter();
                break;
            case 'year':
                $start = Carbon::now()->startOfYear();
                break;
            default:
                $start = Carbon::now()->startOfMonth();
        }
        
        return [
            'start' => $start,
            'end' => $end
        ];
    }

    /**
     * Generate cache key
     * 
     * @param string $prefix
     * @param array $params
     * @return string
     */
    public static function generateCacheKey(string $prefix, array $params = []): string
    {
        $key = $prefix;
        
        foreach ($params as $param => $value) {
            $key .= ":{$param}:{$value}";
        }
        
        return $key;
    }
}