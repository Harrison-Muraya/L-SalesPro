<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'is_manager' => $this->isManager(),
            'unread_notifications_count' => $this->when(
                $request->user() && $request->user()->id === $this->id,
                function() {
                    try {
                        return $this->unreadNotificationsCount();
                    } catch (\Exception $e) {
                        return 0;
                    }
                }
            ),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}