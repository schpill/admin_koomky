<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Settings\UpdateInvoicingSettingsRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoicingSettingsController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success($this->payload($user), 'Invoicing settings retrieved successfully');
    }

    public function update(UpdateInvoicingSettingsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->update($request->validated());

        return $this->success($this->payload($user->fresh()), 'Invoicing settings updated successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        return [
            'payment_terms_days' => $user->payment_terms_days,
            'bank_details' => $user->bank_details,
            'invoice_footer' => $user->invoice_footer,
            'invoice_numbering_pattern' => $user->invoice_numbering_pattern,
        ];
    }
}
