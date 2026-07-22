<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_id' => 'nullable|exists:attendance_locations,id',
            'device' => 'nullable|string',
            'remarks' => 'nullable|string',
            'face_score' => 'nullable|numeric|min:0|max:100',
            'face_status' => 'nullable|string|in:matched,unmatched',
            'photo_data' => 'nullable|string',
            'address' => 'nullable|string',
        ];
    }
}
