<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorScheduleRequest extends FormRequest
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
            'schedules'                       => 'required|array',
            'schedules.*.day_of_week'         => 'required|integer|min:0|max:6',
            'schedules.*.is_active'           => 'sometimes|boolean',
            'schedules.*.slots'               => 'nullable|array',
            'schedules.*.slots.*.id'          => 'sometimes|integer|exists:time_slots,id',
            'schedules.*.slots.*.start_time'  => 'required_without:schedules.*.slots.*.id|date_format:H:i:s',
            'schedules.*.slots.*.end_time'    => 'required_without:schedules.*.slots.*.id|date_format:H:i:s',
            'schedules.*.slots.*.is_blocked'  => 'sometimes|boolean',
        ];
    }
}
