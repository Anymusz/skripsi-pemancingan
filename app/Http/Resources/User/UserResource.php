<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'validation_status' => $this->validation_status,
            'member_id' => $this->member_id,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name');
            }),
            'membership' => $this->whenLoaded('membership', function () {
                return [
                    'tier' => $this->membership->tier,
                    'total_points' => $this->membership->total_points,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
