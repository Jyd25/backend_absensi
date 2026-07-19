<?php

namespace App\Http\Resources;

use App\Http\Resources\Auth\DepartmentResource;
use App\Http\Resources\Auth\PositionResource;
use App\Http\Resources\Auth\ScheduleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nik' => $this->nik,
            'name' => $this->name,
            'gender' => $this->gender?->value,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->toDateString(),
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'position' => new PositionResource($this->whenLoaded('position')),
            'schedule' => new ScheduleResource($this->whenLoaded('schedule')),
            'photo' => $this->photo,
            'is_active' => $this->is_active,
            'users' => $this->whenLoaded('user'),
            'created_at' => $this->created_at,
        ];
    }
}
