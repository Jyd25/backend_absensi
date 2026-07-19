<?php

namespace App\Http\Resources;

use App\Http\Resources\Auth\EmployeeResource as AuthEmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee' => new AuthEmployeeResource($this->whenLoaded('employee')),
            'location' => $this->whenLoaded('location', function () {
                return [
                    'id' => $this->location->id,
                    'location_name' => $this->location->location_name,
                    'latitude' => $this->location->latitude,
                    'longitude' => $this->location->longitude,
                    'radius' => $this->location->radius,
                ];
            }),
            'schedule' => $this->whenLoaded('schedule', function () {
                return [
                    'id' => $this->schedule->id,
                    'name' => $this->schedule->name,
                    'start_time' => $this->schedule->start_time?->format('H:i'),
                    'end_time' => $this->schedule->end_time?->format('H:i'),
                ];
            }),
            'attendance_type' => $this->attendance_type?->value,
            'check_in_time' => $this->check_in_time?->toISOString(),
            'check_out_time' => $this->check_out_time?->toISOString(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance' => $this->distance,
            'face_score' => $this->face_score,
            'location_status' => $this->location_status?->label(),
            'face_status' => $this->face_status?->label(),
            'attendance_status' => $this->attendance_status?->label(),
            'device' => $this->device,
            'ip_address' => $this->ip_address,
            'remarks' => $this->remarks,
            'photo_path' => $this->photo_path,
            'work_duration' => $this->work_duration,
            'created_at' => $this->created_at,
        ];
    }
}
