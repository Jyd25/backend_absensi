<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nik' => 'required|string|max:20|unique:employees,nik',
            'name' => 'required|string|max:100',
            'gender' => 'required|in:male,female',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:employees,email',
            'address' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'schedule_id' => 'required|exists:work_schedules,id',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'sometimes|boolean',
            'user_password' => 'nullable|string|min:6',
        ];
    }
}
