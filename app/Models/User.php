<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'role',
        'permissions',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }
     /**
     * Check if the user has a specific permission.
     * @param string $permission
     * @return bool
     */
    
    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'Sales Manager') {
            return true; // Sales Managers have all permissions
        }
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Check if the user is a Sales Manager.
     * @return bool
     */
    public function isManager(): bool
    {
        return $this->role === 'Sales Manager';
    }

    /**
     * Check if the user is active.
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'Active';
    }


    /**
     * Get the user's full name.
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }


    /**
     * Get the orders created by the user.
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'created_by');
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }

    /**
     * Get the activity logs for the user.
     */
    public function activityLogs()
    {
        return $this->hasMany(\App\Models\ActivityLog::class);
    }   

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->where('is_read', null)->count();
    }


}
