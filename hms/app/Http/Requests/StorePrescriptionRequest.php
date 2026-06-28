<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
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
            'appointment_id'            => ['required', 'integer', 'exists:appointments,id'],
            'notes'                     => ['nullable', 'string', 'max:2000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.medicine_id'       => ['required', 'integer', 'exists:medicines,id'],
            'items.*.dosage'            => ['required', 'string', 'max:100'],
            'items.*.frequency'         => ['required', 'string', 'max:100'],
            'items.*.duration'          => ['required', 'string', 'max:100'],
        ];
    }
}
