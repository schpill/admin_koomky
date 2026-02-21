<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Support\DTOs\DocumentTypeResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DocumentTypeDetectorService
{
    private const DANGEROUS_MIMES = [
        'application/x-executable',
        'application/x-dosexec',
        'application/x-sharedlib',
        'application/x-object',
        'application/x-msdownload',
        'application/x-ms-shortcut',
        'application/x-sh', // shell scripts are handled as scripts but check content
    ];

    private const MIME_MAP = [
        'application/pdf' => DocumentType::PDF,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => DocumentType::SPREADSHEET,
        'application/vnd.ms-excel' => DocumentType::SPREADSHEET,
        'text/csv' => DocumentType::SPREADSHEET,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => DocumentType::DOCUMENT,
        'application/msword' => DocumentType::DOCUMENT,
        'application/vnd.oasis.opendocument.text' => DocumentType::DOCUMENT,
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => DocumentType::PRESENTATION,
        'application/vnd.ms-powerpoint' => DocumentType::PRESENTATION,
        'text/plain' => DocumentType::TEXT,
        'text/markdown' => DocumentType::TEXT,
        'image/jpeg' => DocumentType::IMAGE,
        'image/png' => DocumentType::IMAGE,
        'image/gif' => DocumentType::IMAGE,
        'image/svg+xml' => DocumentType::IMAGE,
        'image/webp' => DocumentType::IMAGE,
        'application/zip' => DocumentType::ARCHIVE,
        'application/x-tar' => DocumentType::ARCHIVE,
        'application/gzip' => DocumentType::ARCHIVE,
        'application/x-7z-compressed' => DocumentType::ARCHIVE,
    ];

    private const SCRIPT_EXTENSIONS = [
        'py' => 'python',
        'php' => 'php',
        'js' => 'javascript',
        'ts' => 'typescript',
        'html' => 'html',
        'css' => 'css',
        'sh' => 'shell',
        'rb' => 'ruby',
        'go' => 'go',
    ];

    public function detect(UploadedFile $file): DocumentTypeResult
    {
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        $extension = Str::lower($file->getClientOriginalExtension());

        if (in_array($mimeType, self::DANGEROUS_MIMES)) {
            // Check if it's a script we actually want to allow
            if (! isset(self::SCRIPT_EXTENSIONS[$extension])) {
                throw new \InvalidArgumentException("Dangerous file type rejected: {$mimeType}");
            }
        }

        // Special handling for scripts based on extension
        if (isset(self::SCRIPT_EXTENSIONS[$extension])) {
            return new DocumentTypeResult(
                DocumentType::SCRIPT,
                self::SCRIPT_EXTENSIONS[$extension],
                $mimeType
            );
        }

        // Map MIME to DocumentType
        $type = self::MIME_MAP[$mimeType] ?? DocumentType::OTHER;

        return new DocumentTypeResult($type, null, $mimeType);
    }
}
