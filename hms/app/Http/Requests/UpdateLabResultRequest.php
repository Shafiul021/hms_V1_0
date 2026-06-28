<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLabResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Role checked via route middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'result_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes'       => ['nullable', 'string', 'max:2000'],
            'is_abnormal' => ['nullable', 'boolean'],
            'result_at'   => ['nullable', 'date'],
        ];
    }
}
