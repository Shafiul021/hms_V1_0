<x-mail::message>
# Appointment Update

Dear **{{ $patient->full_name ?? 'Patient' }}**,

Your appointment has been **{{ ucfirst($appointment->status->value) }}**.

---

<x-mail::panel>
**Appointment Details**

| Field       | Details                                  |
|-------------|------------------------------------------|
| Date        | {{ $appointment->appointment_date?->format('D, d M Y') ?? 'N/A' }} |
| Time        | {{ $appointment->appointment_time?->format('h:i A') ?? 'N/A' }} |
| Doctor      | Dr. {{ $doctor->full_name ?? 'N/A' }}    |
| Speciality  | {{ $doctor->speciality ?? 'N/A' }}       |
| Notes       | {{ $appointment->notes ?? '—' }}         |
</x-mail::panel>

@if($appointment->status->value === 'cancelled')
> We apologise for any inconvenience. Please contact us to reschedule.
@elseif($appointment->status->value === 'confirmed')
> Please arrive **15 minutes** before your scheduled time.
@endif

If you have any questions, please do not hesitate to contact us.

<x-mail::button :url="config('app.url')">
Visit HMS Portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
