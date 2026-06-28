<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bill_id'      => ['required', 'integer', 'exists:bills,id'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'method'       => ['required', 'string', 'in:cash,card,online'],
            'reference_no' => ['nullable', 'string', 'max:255'],
        ];
    }
}
