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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'descriptor' => 'required|string',
            'force' => 'sometimes',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $raw = $this->input('descriptor');
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (!is_array($decoded) || count($decoded) < 128) {
                    $validator->errors()->add('descriptor', 'Descriptor harus berupa array minimal 128 elemen.');
                } else {
                    $this->merge(['descriptor' => $decoded]);
                }
            }
        });
    }
}
