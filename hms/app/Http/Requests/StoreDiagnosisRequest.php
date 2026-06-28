<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisRequest extends FormRequest
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
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'icd_code'       => ['nullable', 'string', 'max:20'],
            'description'    => ['required', 'string', 'max:1000'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            'diagnosed_at'   => ['nullable', 'date'],
        ];
    }
}
