<?php

namespace App\Observers;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\TimeSlot;
use Carbon\Carbon;

class DoctorObserver
{
    /**
     * Handle the Doctor "created" event.
     */
    public function created(Doctor $doctor): void
    {
        // Mon-Fri schedule
        for ($day = 1; $day <= 5; $day++) {
            $schedule = DoctorSchedule::create([
                'doctor_id'   => $doctor->id,
                'day_of_week' => $day,
                'is_active'   => true,
            ]);

            $startTime = Carbon::createFromTime(9, 0, 0);
            $endTime = Carbon::createFromTime(17, 0, 0);

            while ($startTime->lessThan($endTime)) {
                $slotStart = $startTime->toTimeString();
                $startTime->addMinutes(30);
                $slotEnd = $startTime->toTimeString();

                TimeSlot::create([
                    'doctor_schedule_id' => $schedule->id,
                    'start_time'         => $slotStart,
                    'end_time'           => $slotEnd,
                    'is_blocked'         => false,
                ]);
            }
        }
    }
}
