<?php

namespace App\Http\Requests\WorkSchedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
            'tolerance_minutes' => 'nullable|integer|min:0|max:120',
            'working_days' => 'sometimes|required|array|min:1',
            'working_days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
