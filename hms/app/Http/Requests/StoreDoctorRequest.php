<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|max:255|unique:users,email',
            'password'       => 'nullable|string|min:8',
            'specialization' => 'required|string|max:255',
            'qualification'  => 'required|string|max:255',
            'fee'            => 'required|numeric|min:0',
        ];
    }
}
