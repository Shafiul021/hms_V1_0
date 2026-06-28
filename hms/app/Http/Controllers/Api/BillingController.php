<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBillRequest;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class BillingController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Auto-generate a bill from an appointment.
     */
    public function generate(StoreBillRequest $request): JsonResponse|BillResource
    {
        try {
            $bill = $this->billingService->generate($request->validated());
            
            return new BillResource(
                $bill->load(['patient.user', 'appointment.doctor.user', 'items', 'payments'])
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified bill.
     */
    public function show(int $id): JsonResponse|BillResource
    {
        $bill = Bill::with([
            'patient.user', 
            'appointment.doctor.user', 
            'items', 
            'payments.recordedBy'
        ])->findOrFail($id);

        $user = auth()->user();
        if ($user->hasRole('patient') && $bill->patient->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access to this bill.'], 403);
        }

        return new BillResource($bill);
    }

    /**
     * Download the invoice as a PDF.
     */
    public function downloadPdf(int $id)
    {
        $bill = Bill::with([
            'patient.user', 
            'appointment.doctor.user', 
            'items', 
            'payments.recordedBy'
        ])->findOrFail($id);

        $user = auth()->user();
        if ($user->hasRole('patient') && $bill->patient->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized access to this invoice.'], 403);
        }

        $pdf = Pdf::loadView('pdf.invoice', compact('bill'));
        
        return $pdf->download("invoice_{$bill->id}.pdf");
    }
}
