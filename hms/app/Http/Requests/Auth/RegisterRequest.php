<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Any authenticated user may hit the register endpoint (open registration).
     * To restrict registration to admins only, change this to:
     *   return $this->user()?->hasRole('admin');
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for a new user registration.
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['sometimes', 'string', 'in:admin,doctor,receptionist,nurse,patient'],
        ];
    }

    /**
     * Custom human-readable attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'password' => 'password',
        ];
    }
}
