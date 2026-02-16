<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invoices\StorePaymentRequest;
use App\Http\Resources\Api\V1\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    use ApiResponse;

    public function store(StorePaymentRequest $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('update', $invoice);

        $validated = $request->validated();
        $amount = round((float) $validated['amount'], 2);

        if ($amount > (float) $invoice->balance_due) {
            return $this->error('Payment amount exceeds invoice balance', 422);
        }

        $invoice->payments()->create([
            'amount' => $amount,
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $invoice->refresh();

        $amountPaid = (float) $invoice->amount_paid;
        $total = (float) $invoice->total;

        if ($amountPaid >= $total) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } elseif ($amountPaid > 0 && $invoice->status !== 'partially_paid') {
            $invoice->update([
                'status' => 'partially_paid',
            ]);
        }

        $invoice->refresh()->load(['client', 'project', 'lineItems', 'payments', 'creditNotes']);

        return $this->success(new InvoiceResource($invoice), 'Payment recorded successfully', 201);
    }
}
