<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use App\Models\Bill;
use App\Models\Prescription;
use App\Models\LabResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PatientService
{
    /**
     * Create a new patient user and profile.
     */
    public function create(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password123'),
            ]);

            $user->assignRole('patient');

            $patient = Patient::create([
                'user_id'    => $user->id,
                'dob'        => $data['dob'],
                'blood_type' => $data['blood_type'],
                'gender'     => $data['gender'],
            ]);

            if (!empty($data['allergies'])) {
                foreach ($data['allergies'] as $allergy) {
                    $patient->allergies()->create([
                        'allergen' => $allergy['allergen'],
                        'severity' => $allergy['severity'],
                        'notes'    => $allergy['notes'] ?? null,
                    ]);
                }
            }

            if (!empty($data['emergency_contacts'])) {
                foreach ($data['emergency_contacts'] as $contact) {
                    $patient->emergencyContacts()->create([
                        'name'         => $contact['name'],
                        'relationship' => $contact['relationship'],
                        'phone'        => $contact['phone'],
                    ]);
                }
            }

            return $patient->load(['user', 'allergies', 'emergencyContacts']);
        });
    }

    /**
     * Update an existing patient user and profile.
     */
    public function update(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data) {
            // Update User details
            $userFields = [];
            if (isset($data['name'])) {
                $userFields['name'] = $data['name'];
            }
            if (isset($data['email'])) {
                $userFields['email'] = $data['email'];
            }
            if (isset($data['password'])) {
                $userFields['password'] = Hash::make($data['password']);
            }
            if (!empty($userFields)) {
                $patient->user()->update($userFields);
            }

            // Update Patient details
            $patientFields = [];
            if (isset($data['dob'])) {
                $patientFields['dob'] = $data['dob'];
            }
            if (isset($data['blood_type'])) {
                $patientFields['blood_type'] = $data['blood_type'];
            }
            if (isset($data['gender'])) {
                $patientFields['gender'] = $data['gender'];
            }
            if (!empty($patientFields)) {
                $patient->update($patientFields);
            }

            // Update Allergies if provided
            if (isset($data['allergies'])) {
                $patient->allergies()->delete();
                foreach ($data['allergies'] as $allergy) {
                    $patient->allergies()->create([
                        'allergen' => $allergy['allergen'],
                        'severity' => $allergy['severity'],
                        'notes'    => $allergy['notes'] ?? null,
                    ]);
                }
            }

            // Update Emergency Contacts if provided
            if (isset($data['emergency_contacts'])) {
                $patient->emergencyContacts()->delete();
                foreach ($data['emergency_contacts'] as $contact) {
                    $patient->emergencyContacts()->create([
                        'name'         => $contact['name'],
                        'relationship' => $contact['relationship'],
                        'phone'        => $contact['phone'],
                    ]);
                }
            }

            return $patient->load(['user', 'allergies', 'emergencyContacts']);
        });
    }

    /**
     * Aggregate full medical records for history.
     */
    public function getMedicalHistory(Patient $patient): array
    {
        $patient->load([
            'user',
            'allergies',
            'emergencyContacts',
            'appointments.doctor.user',
            'appointments.diagnosis',
            'appointments.prescription.items.medicine',
            'appointments.labRequests.test',
            'appointments.labRequests.result',
            'admissions.ward',
            'admissions.bed',
            'admissions.doctor.user',
            'admissions.nursingNotes.nurse',
        ]);

        return [
            'patient'            => $patient,
            'allergies'          => $patient->allergies,
            'emergency_contacts' => $patient->emergencyContacts,
            'diagnoses'          => $patient->appointments->pluck('diagnosis')->filter()->values(),
            'prescriptions'      => $patient->appointments->pluck('prescription')->filter()->values(),
            'lab_requests'       => $patient->appointments->flatMap->labRequests->values(),
            'admissions'         => $patient->admissions,
        ];
    }
}
