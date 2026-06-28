<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
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
            'patient_id' => 'required|integer|exists:patients,id',
            'doctor_id'  => 'required|integer|exists:doctors,id',
            'slot_id'    => 'required|integer|exists:time_slots,id',
            'date'       => 'required|date|after_or_equal:today',
            'notes'      => 'nullable|string|max:1000',
        ];
    }
}
