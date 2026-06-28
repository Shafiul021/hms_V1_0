<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use App\Models\Admission;
use App\Models\Appointment;
use Hms\Core\Enums\BillStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingService
{
    /**
     * Auto-generate a bill from an appointment.
     */
    public function generate(array $data): Bill
    {
        return DB::transaction(function () use ($data) {
            $appointmentId = $data['appointment_id'];
            $appointment = Appointment::with([
                'doctor.user', 
                'prescription.items.medicine', 
                'labRequests.test', 
                'patient'
            ])->findOrFail($appointmentId);

            if ($appointment->bill) {
                throw new \InvalidArgumentException("A bill has already been generated for this appointment.");
            }

            $patientId = $appointment->patient_id;

            // Create initial bill record
            $bill = Bill::create([
                'patient_id'     => $patientId,
                'appointment_id' => $appointment->id,
                'status'         => BillStatus::Issued,
                'total_amount'   => 0.00,
                'paid_amount'    => 0.00,
                'due_date'       => now()->addDays(14),
                'issued_at'      => now(),
            ]);

            $totalAmount = 0.00;

            // 1. Consultation Fee
            if ($appointment->doctor && $appointment->doctor->fee > 0) {
                $fee = $appointment->doctor->fee;
                BillItem::create([
                    'bill_id'     => $bill->id,
                    'item_type'   => 'consultation',
                    'description' => "Consultation Fee - Dr. {$appointment->doctor->user->name}",
                    'quantity'    => 1,
                    'unit_price'  => $fee,
                    'total'       => $fee,
                ]);
                $totalAmount += $fee;
            }

            // 2. Lab Fees
            foreach ($appointment->labRequests as $labRequest) {
                if ($labRequest->test) {
                    $price = $labRequest->test->price;
                    BillItem::create([
                        'bill_id'     => $bill->id,
                        'item_type'   => 'lab',
                        'description' => "Lab Test - {$labRequest->test->name}",
                        'quantity'    => 1,
                        'unit_price'  => $price,
                        'total'       => $price,
                    ]);
                    $totalAmount += $price;
                }
            }

            // 3. Medicine Cost from Prescription
            if ($appointment->prescription) {
                foreach ($appointment->prescription->items as $prescriptionItem) {
                    if ($prescriptionItem->medicine) {
                        $price = $prescriptionItem->medicine->price;
                        
                        // Parse duration and frequency to estimate quantity
                        $quantity = 1;
                        if (preg_match('/(\d+)/', $prescriptionItem->duration, $matches)) {
                            $durationVal = (int) $matches[1];
                            
                            $freqVal = 1;
                            $freqStr = strtoupper(trim($prescriptionItem->frequency));
                            if ($freqStr === 'TID') {
                                $freqVal = 3;
                            } elseif ($freqStr === 'BD') {
                                $freqVal = 2;
                            } elseif ($freqStr === 'QD' || $freqStr === 'OD') {
                                $freqVal = 1;
                            } elseif (preg_match('/(\d+)/', $freqStr, $freqMatches)) {
                                $freqVal = (int) $freqMatches[1];
                            }
                            
                            $quantity = $durationVal * $freqVal;
                        }
                        
                        $itemTotal = $quantity * $price;
                        
                        BillItem::create([
                            'bill_id'     => $bill->id,
                            'item_type'   => 'medicine',
                            'description' => "Medicine - {$prescriptionItem->medicine->name}",
                            'quantity'    => $quantity,
                            'unit_price'  => $price,
                            'total'       => $itemTotal,
                        ]);
                        $totalAmount += $itemTotal;
                    }
                }
            }

            // 4. Bed Charge from Admission
            // Find an active or recently discharged admission that hasn't been billed yet
            $admission = Admission::where('patient_id', $patientId)
                ->whereNull('discharged_at')
                ->first();
                
            if (!$admission) {
                $admission = Admission::where('patient_id', $patientId)
                    ->whereNotNull('discharged_at')
                    ->orderBy('discharged_at', 'desc')
                    ->first();
            }

            if ($admission && $admission->bed && $admission->bed->ward) {
                // Heuristic check: is this admission already billed for this patient?
                $alreadyBilled = BillItem::where('item_type', 'bed')
                    ->where('description', 'like', "%Bed #{$admission->bed->bed_number}%")
                    ->whereHas('bill', fn($q) => $q->where('patient_id', $patientId))
                    ->exists();

                if (!$alreadyBilled) {
                    $admittedAt = Carbon::parse($admission->admitted_at);
                    $dischargedAt = $admission->discharged_at ? Carbon::parse($admission->discharged_at) : now();
                    $days = max(1, (int) ceil($admittedAt->diffInDays($dischargedAt)));
                    $dailyRate = $admission->bed->ward->daily_rate;
                    $bedTotal = $days * $dailyRate;

                    BillItem::create([
                        'bill_id'     => $bill->id,
                        'item_type'   => 'bed',
                        'description' => "Bed Charge - {$admission->bed->ward->name} (Bed #{$admission->bed->bed_number})",
                        'quantity'    => $days,
                        'unit_price'  => $dailyRate,
                        'total'       => $bedTotal,
                    ]);
                    $totalAmount += $bedTotal;
                }
            }

            // Update total amount on the bill
            $bill->update([
                'total_amount' => $totalAmount,
            ]);

            return $bill;
        });
    }

    /**
     * Record a payment and update the bill status.
     */
    public function recordPayment(array $data, int $userId): Payment
    {
        return DB::transaction(function () use ($data, $userId) {
            $bill = Bill::findOrFail($data['bill_id']);

            $payment = Payment::create([
                'bill_id'      => $bill->id,
                'amount'       => $data['amount'],
                'method'       => $data['method'],
                'reference_no' => $data['reference_no'] ?? null,
                'paid_at'      => now(),
                'recorded_by'  => $userId,
            ]);

            $newPaidAmount = $bill->paid_amount + $data['amount'];
            
            $status = BillStatus::Partial;
            if ($newPaidAmount >= $bill->total_amount) {
                $status = BillStatus::Paid;
            }

            $bill->update([
                'paid_amount' => $newPaidAmount,
                'status'      => $status,
            ]);

            return $payment;
        });
    }
}
