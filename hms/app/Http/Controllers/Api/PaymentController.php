<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Services\BillingService;

class PaymentController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Record a payment for a bill.
     */
    public function store(StorePaymentRequest $request): PaymentResource
    {
        $payment = $this->billingService->recordPayment($request->validated(), auth()->id());

        return new PaymentResource($payment->load('recordedBy'));
    }
}
