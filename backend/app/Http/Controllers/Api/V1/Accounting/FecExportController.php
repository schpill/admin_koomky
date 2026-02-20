<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FecExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FecExportController extends Controller
{
    use ApiResponse;

    public function count(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        /** @var User $user */
        $user = $request->user();

        $service = new FecExportService;
        $count = $service->getEntryCount($user, [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        return $this->success(['entry_count' => $count], 'FEC entry count retrieved successfully');
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        /** @var User $user */
        $user = $request->user();

        $service = new FecExportService;
        $generator = $service->generate($user, [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        $filename = "FEC{$user->id}_{$request->date_from}_{$request->date_to}.txt";

        $headers = [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($generator): void {
            foreach ($generator as $line) {
                echo $line."\n";
            }
        }, 200, $headers);
    }
}
