<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ExpenseReceiptService
{
    /**
     * @return array<string, string|null>
     */
    public function upload(Expense $expense, UploadedFile $receipt): array
    {
        $mimeType = (string) $receipt->getClientMimeType();
        $isImage = str_starts_with($mimeType, 'image/');
        $isPdf = $mimeType === 'application/pdf';

        if (! $isImage && ! $isPdf) {
            throw new RuntimeException('Unsupported receipt file type');
        }

        $disk = Storage::disk('receipts');

        if (is_string($expense->receipt_path) && $expense->receipt_path !== '') {
            $disk->delete($expense->receipt_path);
        }

        $directory = 'expenses/'.$expense->user_id.'/'.$expense->id;
        $storedPath = $disk->putFile($directory, $receipt);

        if (! is_string($storedPath) || $storedPath === '') {
            throw new RuntimeException('Unable to store receipt');
        }

        $thumbnailPath = null;
        if ($isImage && $disk->exists($storedPath)) {
            $thumbnailPath = preg_replace('/(\.[a-zA-Z0-9]+)$/', '-thumb$1', $storedPath) ?: $storedPath.'-thumb';
            $disk->put($thumbnailPath, (string) $disk->get($storedPath));
        }

        $expense->forceFill([
            'receipt_path' => $storedPath,
            'receipt_filename' => $receipt->getClientOriginalName(),
            'receipt_mime_type' => $mimeType,
        ])->save();

        return [
            'path' => $storedPath,
            'filename' => $expense->receipt_filename,
            'mime_type' => $mimeType,
            'thumbnail_path' => $thumbnailPath,
        ];
    }

    public function delete(Expense $expense): void
    {
        $disk = Storage::disk('receipts');

        if (is_string($expense->receipt_path) && $expense->receipt_path !== '') {
            $disk->delete($expense->receipt_path);
            $thumbnailPath = preg_replace('/(\.[a-zA-Z0-9]+)$/', '-thumb$1', $expense->receipt_path) ?: $expense->receipt_path.'-thumb';
            $disk->delete($thumbnailPath);
        }

        $expense->forceFill([
            'receipt_path' => null,
            'receipt_filename' => null,
            'receipt_mime_type' => null,
        ])->save();
    }
}
