<?php

namespace App\Observers;

use App\Models\Patient;

class PatientObserver
{
    /**
     * Handle the Patient "creating" event.
     * Auto-generates a unique patient code: HMS-YYYY-XXXXX before insertion.
     */
    public function creating(Patient $patient): void
    {
        $year = now()->year;
        $nextId = (Patient::max('id') ?? 0) + 1;
        $patient->patient_code = sprintf('HMS-%d-%05d', $year, $nextId);
    }


    /**
     * Handle the Patient "updated" event.
     */
    public function updated(Patient $patient): void
    {
        //
    }

    /**
     * Handle the Patient "deleted" event.
     */
    public function deleted(Patient $patient): void
    {
        //
    }

    /**
     * Handle the Patient "restored" event.
     */
    public function restored(Patient $patient): void
    {
        //
    }

    /**
     * Handle the Patient "force deleted" event.
     */
    public function forceDeleted(Patient $patient): void
    {
        //
    }
}
