<?php

namespace App\Http\Requests\Face;

use Illuminate\Foundation\Http\FormRequest;

class VerifyFaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'descriptor' => 'required|array|min:128',
            'descriptor.*' => 'numeric',
            'threshold' => 'sometimes|numeric|min:0.40|max:0.65',
        ];
    }
}
