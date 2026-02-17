<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountDeletionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AccountDeletionService $accountDeletionService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $scheduledAt = $this->accountDeletionService->schedule($user);

        return $this->success([
            'scheduled_purge_at' => $scheduledAt->toIso8601String(),
        ], 'Account deletion scheduled');
    }
}
