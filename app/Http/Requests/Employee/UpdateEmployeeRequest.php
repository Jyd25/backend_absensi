<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id ?? $this->route('employee');

        return [
            'nik' => 'sometimes|required|string|max:20|unique:employees,nik,' . $employeeId,
            'name' => 'sometimes|required|string|max:100',
            'gender' => 'sometimes|required|in:male,female',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'phone' => 'nullable|string|max:20',
            'email' => 'sometimes|required|email|unique:employees,email,' . $employeeId,
            'address' => 'nullable|string',
            'department_id' => 'sometimes|required|exists:departments,id',
            'position_id' => 'sometimes|required|exists:positions,id',
            'schedule_id' => 'sometimes|required|exists:work_schedules,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
