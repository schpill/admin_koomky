<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentStorageService
{
    private const DISK = 'local';

    private const BASE_PATH = 'documents';

    public function store(UploadedFile $file, User $user): string
    {
        $this->checkQuota($file->getSize(), $user);

        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $directory = self::BASE_PATH."/{$user->id}";
        $filename = "{$uuid}.{$extension}";

        $path = $file->storeAs($directory, $filename, self::DISK);

        if ($path === false) {
            throw new \RuntimeException('Failed to store the document.');
        }

        return $path;
    }

    public function overwrite(string $path, UploadedFile $file, User $user): void
    {
        $this->checkQuota($file->getSize(), $user);

        Storage::disk(self::DISK)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );
    }

    public function delete(string $path): void
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function streamDownload(string $path, string $mimeType, string $filename, bool $inline = false): StreamedResponse
    {
        if (! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        $disposition = $inline ? 'inline' : 'attachment';

        return Storage::disk(self::DISK)->download($path, $filename, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }

    public function checkQuota(int $newFileSize, User $user): void
    {
        $quotaBytes = $user->document_storage_quota_mb * 1024 * 1024;
        $usedBytes = $user->documents()->sum('file_size');

        if (($usedBytes + $newFileSize) > $quotaBytes) {
            throw new \RuntimeException('Storage quota exceeded');
        }
    }

    public function getTotalUsedBytes(User $user): int
    {
        return (int) $user->documents()->sum('file_size');
    }
}
