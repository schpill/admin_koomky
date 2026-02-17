<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CampaignRecipient;
use App\Services\EmailTrackingTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    public function __construct(private readonly EmailTrackingTokenService $tokenService) {}

    public function open(string $token): Response
    {
        $recipient = $this->resolveRecipient($token);
        if ($recipient === null) {
            abort(404);
        }

        if ($recipient->opened_at === null) {
            $recipient->update([
                'opened_at' => now(),
                'status' => in_array($recipient->status, ['sent', 'delivered'], true) ? 'opened' : $recipient->status,
            ]);
        }

        $pixel = base64_decode('R0lGODlhAQABAIABAP///wAAACwAAAAAAQABAAACAkQBADs=', true) ?: '';

        return response($pixel, 200, ['Content-Type' => 'image/gif']);
    }

    public function click(Request $request, string $token): RedirectResponse
    {
        $recipient = $this->resolveRecipient($token);
        if ($recipient === null) {
            abort(404);
        }

        if ($recipient->clicked_at === null) {
            $recipient->update([
                'clicked_at' => now(),
                'status' => 'clicked',
            ]);
        }

        $url = (string) $request->query('url', '/');

        return redirect()->away($url);
    }

    private function resolveRecipient(string $token): ?CampaignRecipient
    {
        $recipientId = $this->tokenService->decode($token);
        if ($recipientId === null) {
            return null;
        }

        return CampaignRecipient::query()->find($recipientId);
    }
}
