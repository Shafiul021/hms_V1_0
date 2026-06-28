<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreNursingNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Role checked via route middleware
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note'        => ['required', 'string', 'max:2000'],
            'recorded_at' => ['nullable', 'date'],
        ];
    }
}
