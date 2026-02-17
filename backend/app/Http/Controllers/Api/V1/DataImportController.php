<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DataImportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DataImportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DataImportService $dataImportService
    ) {}

    public function import(Request $request, string $entity): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('file');
        if (! $file instanceof \Illuminate\Http\UploadedFile) {
            return $this->error('Invalid upload payload', 422);
        }

        try {
            $result = $this->dataImportService->import($user, $entity, $file);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->success($result, 'Import completed');
    }
}
