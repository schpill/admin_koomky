<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadConversionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadConversionController extends Controller
{
    use ApiResponse;

    public function convert(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $lead = Lead::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|nullable|email',
            'phone' => 'sometimes|nullable|string',
        ]);

        $service = new LeadConversionService;

        try {
            $client = $service->convert($lead, $request->only(['name', 'email', 'phone']));

            return $this->success([
                'client' => $client,
                'lead' => $lead->fresh(),
            ], 'Lead converted to client successfully');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
