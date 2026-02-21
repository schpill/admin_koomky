<?php

namespace App\Support\DTOs;

use App\Enums\DocumentType;

class DocumentTypeResult
{
    public function __construct(
        public readonly DocumentType $document_type,
        public readonly ?string $script_language,
        public readonly string $mime_type
    ) {}
}
