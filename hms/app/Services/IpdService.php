<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\NursingNote;
use Hms\Core\Enums\BedStatus;
use Illuminate\Support\Facades\DB;

class IpdService
{
    /**
     * Admit a patient to a bed.
     * - Validates the bed is Available.
     * - Creates the Admission record.
     * - Sets bed status to Occupied.
     */
    public function admit(array $data): Admission
    {
        return DB::transaction(function () use ($data) {
            $bed = Bed::findOrFail($data['bed_id']);

            if ($bed->status !== BedStatus::Available) {
                abort(422, "Bed #{$bed->bed_number} is not available (status: {$bed->status->value}).");
            }

            $admission = Admission::create([
                'patient_id'  => $data['patient_id'],
                'bed_id'      => $bed->id,
                'doctor_id'   => $data['doctor_id'],
                'reason'      => $data['reason'],
                'notes'       => $data['notes'] ?? null,
                'admitted_at' => $data['admitted_at'] ?? now(),
            ]);

            $bed->update(['status' => BedStatus::Occupied]);

            return $admission->load('patient.user', 'doctor.user', 'bed.ward');
        });
    }

    /**
     * Discharge a patient from their bed.
     * - Sets discharged_at timestamp.
     * - Frees the bed back to Available.
     */
    public function discharge(Admission $admission): Admission
    {
        return DB::transaction(function () use ($admission) {
            if (! is_null($admission->discharged_at)) {
                abort(422, 'Patient has already been discharged.');
            }

            $admission->update(['discharged_at' => now()]);
            $admission->bed->update(['status' => BedStatus::Available]);

            return $admission->fresh(['patient.user', 'doctor.user', 'bed.ward']);
        });
    }

    /**
     * Add a nursing note to an admission.
     */
    public function addNote(Admission $admission, array $data, int $nurseId): NursingNote
    {
        $note = NursingNote::create([
            'admission_id' => $admission->id,
            'nurse_id'     => $nurseId,
            'note'         => $data['note'],
            'recorded_at'  => $data['recorded_at'] ?? now(),
        ]);

        return $note->load('nurse');
    }
}
