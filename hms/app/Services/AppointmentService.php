<?php

namespace App\Services;

use App\Events\AppointmentStatusChanged;
use App\Jobs\SendAppointmentEmail;
use App\Models\Appointment;
use App\Models\AppointmentLog;
use App\Models\TimeSlot;
use App\Models\User;
use Hms\Core\Enums\AppointmentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AppointmentService
{
    /**
     * Book a new appointment.
     *
     * @param array $data
     * @param \App\Models\User $bookedBy
     * @return \App\Models\Appointment
     * @throws \Illuminate\Validation\ValidationException
     */
    public function book(array $data, User $bookedBy): Appointment
    {
        $slotId = $data['slot_id'];
        $date = $data['date'];
        $doctorId = $data['doctor_id'];

        // Retrieve time slot with its schedule
        $slot = TimeSlot::with('schedule')->findOrFail($slotId);

        // 1. Verify slot schedule belongs to doctor
        if ($slot->schedule->doctor_id != $doctorId) {
            throw ValidationException::withMessages([
                'slot_id' => ['The selected time slot does not belong to this doctor.']
            ]);
        }

        // 2. Verify slot is active/not blocked
        if ($slot->is_blocked || !$slot->schedule->is_active) {
            throw ValidationException::withMessages([
                'slot_id' => ['The selected time slot is currently blocked or inactive.']
            ]);
        }

        // 3. Verify slot conflict on the date
        $conflict = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->where('slot_id', $slotId)
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'slot_id' => ['The selected time slot is already booked for this date.']
            ]);
        }

        // 4. Create appointment inside transaction
        return DB::transaction(function () use ($data, $bookedBy) {
            $appointment = Appointment::create([
                'patient_id' => $data['patient_id'],
                'doctor_id'  => $data['doctor_id'],
                'slot_id'    => $data['slot_id'],
                'date'       => $data['date'],
                'status'     => AppointmentStatus::Pending,
                'booked_by'  => $bookedBy->id,
                'notes'      => $data['notes'] ?? null,
            ]);

            // Create initial appointment log
            AppointmentLog::create([
                'appointment_id' => $appointment->id,
                'old_status'     => null,
                'new_status'     => AppointmentStatus::Pending,
                'changed_by'     => $bookedBy->id,
            ]);

            // Dispatch queued email notification job
            SendAppointmentEmail::dispatch($appointment);

            return $appointment;
        });
    }

    /**
     * Update appointment status.
     *
     * @param \App\Models\Appointment $appointment
     * @param \Hms\Core\Enums\AppointmentStatus $newStatus
     * @param \App\Models\User $user
     * @return \App\Models\Appointment
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function updateStatus(Appointment $appointment, AppointmentStatus $newStatus, User $user): Appointment
    {
        $oldStatus = $appointment->status;

        if ($oldStatus === $newStatus) {
            return $appointment;
        }

        // 1. Validate status transition
        $isValid = false;
        switch ($oldStatus) {
            case AppointmentStatus::Pending:
                $isValid = in_array($newStatus, [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled]);
                break;
            case AppointmentStatus::Confirmed:
                $isValid = in_array($newStatus, [AppointmentStatus::InProgress, AppointmentStatus::Cancelled]);
                break;
            case AppointmentStatus::InProgress:
                $isValid = ($newStatus === AppointmentStatus::Completed);
                break;
        }

        if (!$isValid) {
            throw ValidationException::withMessages([
                'status' => ["Transition from {$oldStatus->value} to {$newStatus->value} is not allowed."]
            ]);
        }

        // 2. Validate user role permissions
        $hasRole = false;
        if ($user->hasRole('admin')) {
            $hasRole = true;
        } else {
            switch ($newStatus) {
                case AppointmentStatus::Confirmed:
                    $hasRole = $user->hasRole('receptionist');
                    break;
                case AppointmentStatus::InProgress:
                case AppointmentStatus::Completed:
                    $hasRole = $user->hasRole('doctor');
                    break;
                case AppointmentStatus::Cancelled:
                    if ($user->hasRole('receptionist')) {
                        $hasRole = true;
                    } elseif ($user->hasRole('patient')) {
                        // Patient can only cancel their own appointments
                        $hasRole = ($appointment->patient->user_id === $user->id);
                    }
                    break;
            }
        }

        if (!$hasRole) {
            throw new AccessDeniedHttpException("You do not have permission to transition this appointment to {$newStatus->value}.");
        }

        // 3. Perform update inside transaction
        return DB::transaction(function () use ($appointment, $oldStatus, $newStatus, $user) {
            $appointment->status = $newStatus;
            $appointment->save();

            // Log status change
            AppointmentLog::create([
                'appointment_id' => $appointment->id,
                'old_status'     => $oldStatus,
                'new_status'     => $newStatus,
                'changed_by'     => $user->id,
            ]);

            // Broadcast real-time status changed event
            event(new AppointmentStatusChanged($appointment));

            return $appointment;
        });
    }
}
