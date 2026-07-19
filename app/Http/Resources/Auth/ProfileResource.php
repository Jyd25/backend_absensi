<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => new RoleResource($this->whenLoaded('role')),
            'employee' => $this->whenLoaded('employee', function () {
                $employee = $this->employee;
                return [
                    'id' => $employee->id,
                    'nik' => $employee->nik,
                    'name' => $employee->name,
                    'gender' => $employee->gender?->value,
                    'birth_place' => $employee->birth_place,
                    'birth_date' => $employee->birth_date?->toDateString(),
                    'phone' => $employee->phone,
                    'email' => $employee->email,
                    'address' => $employee->address,
                    'department' => new DepartmentResource($employee->whenLoaded('department')),
                    'position' => new PositionResource($employee->whenLoaded('position')),
                    'schedule' => new ScheduleResource($employee->whenLoaded('schedule')),
                    'photo' => $employee->photo,
                    'is_active' => $employee->is_active,
                ];
            }),
            'status' => $this->status?->value,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
