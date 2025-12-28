<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'category',
        'contact_person',
        'phone',
        'email',
        'tax_id',
        'payment_terms',
        'credit_limit',
        'current_balance',
        'latitude',
        'longitude',
        'address',
        'territory',
    ];

    protected $casts = [
        'payment_terms' => 'integer',
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getAvailableCreditAttribute(): float
    {
        return max(0, $this->credit_limit - $this->current_balance);
    }

    public function getCreditUsagePercentageAttribute(): float
    {
        if ($this->credit_limit == 0) {
            return 0;
        }
        return round(($this->current_balance / $this->credit_limit) * 100, 2);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('contact_person', 'like', "%{$term}%");
        });
    }
}
