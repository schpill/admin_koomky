<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request, Contact $contact): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->error('Invalid or expired unsubscribe link', 403);
        }

        if ($contact->email_unsubscribed_at === null) {
            $contact->forceFill([
                'email_unsubscribed_at' => now(),
            ])->save();
        }

        return $this->success([
            'contact_id' => $contact->id,
            'email_unsubscribed_at' => $contact->email_unsubscribed_at,
        ], 'Unsubscribe processed successfully');
    }
}
