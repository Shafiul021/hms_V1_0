<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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
            'name'                              => 'required|string|max:255',
            'email'                             => 'required|string|email|max:255|unique:users,email',
            'password'                          => 'nullable|string|min:8',
            'dob'                               => 'required|date|before:today',
            'blood_type'                        => 'required|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'gender'                            => 'required|string|in:male,female,other',
            'allergies'                         => 'nullable|array',
            'allergies.*.allergen'              => 'required_with:allergies|string|max:255',
            'allergies.*.severity'              => 'required_with:allergies|string|in:low,medium,high',
            'allergies.*.notes'                 => 'nullable|string|max:1000',
            'emergency_contacts'                => 'nullable|array',
            'emergency_contacts.*.name'         => 'required_with:emergency_contacts|string|max:255',
            'emergency_contacts.*.relationship' => 'required_with:emergency_contacts|string|max:255',
            'emergency_contacts.*.phone'        => 'required_with:emergency_contacts|string|max:255',
        ];
    }
}
