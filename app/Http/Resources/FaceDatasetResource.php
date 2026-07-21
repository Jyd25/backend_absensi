<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FaceDatasetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'image_path' => $this->image_path ?? $this->image_data,
            'image_data' => $this->image_data ?? $this->image_path,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at,
        ];
    }
}
