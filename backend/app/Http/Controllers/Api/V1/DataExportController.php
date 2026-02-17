<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    public function __construct(
        private readonly DataExportService $dataExportService
    ) {}

    public function full(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $archivePath = $this->dataExportService->createArchive($user);
        $filename = sprintf('koomky-export-%s.zip', now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($archivePath): void {
            $stream = fopen($archivePath, 'rb');
            if ($stream === false) {
                return;
            }

            while (! feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false) {
                    break;
                }
                echo $chunk;
            }

            fclose($stream);
            @unlink($archivePath);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
