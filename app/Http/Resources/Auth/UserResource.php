<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => new RoleResource($this->whenLoaded('role')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'employee_id' => $this->employee_id,
            'status' => $this->status?->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
