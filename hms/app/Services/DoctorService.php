<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\Appointment;
use Hms\Core\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorService
{
    /**
     * Create a new doctor user and profile.
     */
    public function create(array $data): Doctor
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password123'),
            ]);

            $user->assignRole('doctor');

            return Doctor::create([
                'user_id'        => $user->id,
                'specialization' => $data['specialization'],
                'qualification'  => $data['qualification'],
                'fee'            => $data['fee'],
            ]);
        });
    }

    /**
     * Get available slots for a doctor on a specific date.
     */
    public function getAvailableSlots(int $doctorId, string $dateString)
    {
        $date = Carbon::parse($dateString);
        $dayOfWeek = $date->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

        // Find already booked slots on this date (that are not cancelled)
        $bookedSlotIds = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date->toDateString())
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->pluck('slot_id')
            ->toArray();

        // Get slots matching day of week, schedule active, and not blocked
        return TimeSlot::whereHas('schedule', function ($query) use ($doctorId, $dayOfWeek) {
            $query->where('doctor_id', $doctorId)
                  ->where('day_of_week', $dayOfWeek)
                  ->where('is_active', true);
        })
        ->where('is_blocked', false)
        ->get()
        ->filter(function ($slot) use ($bookedSlotIds) {
            return !in_array($slot->id, $bookedSlotIds);
        })
        ->values();
    }

    /**
     * Update doctor schedule status and toggle slot blocks.
     */
    public function updateSchedule(Doctor $doctor, array $data): Doctor
    {
        return DB::transaction(function () use ($doctor, $data) {
            if (isset($data['schedules'])) {
                foreach ($data['schedules'] as $schData) {
                    $schedule = DoctorSchedule::updateOrCreate(
                        [
                            'doctor_id'   => $doctor->id,
                            'day_of_week' => $schData['day_of_week'],
                        ],
                        [
                            'is_active' => $schData['is_active'] ?? true,
                        ]
                    );

                    if (isset($schData['slots'])) {
                        foreach ($schData['slots'] as $slotData) {
                            if (isset($slotData['id'])) {
                                TimeSlot::where('doctor_schedule_id', $schedule->id)
                                    ->where('id', $slotData['id'])
                                    ->update([
                                        'is_blocked' => $slotData['is_blocked']
                                    ]);
                            } else {
                                TimeSlot::create([
                                    'doctor_schedule_id' => $schedule->id,
                                    'start_time'         => $slotData['start_time'],
                                    'end_time'           => $slotData['end_time'],
                                    'is_blocked'         => $slotData['is_blocked'] ?? false,
                                ]);
                            }
                        }
                    }
                }
            }

            return $doctor->load(['schedules.slots']);
        });
    }
}
