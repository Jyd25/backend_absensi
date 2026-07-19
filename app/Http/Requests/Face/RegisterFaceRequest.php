<?php

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'descriptor' => 'required|array|min:128',
            'descriptor.*' => 'numeric',
            'force' => 'sometimes|boolean',
        ];
    }
}
