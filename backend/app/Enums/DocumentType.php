<?php

namespace App\Enums;

enum DocumentType: string
{
    case PDF = 'pdf';
    case SPREADSHEET = 'spreadsheet';
    case DOCUMENT = 'document';
    case TEXT = 'text';
    case SCRIPT = 'script';
    case IMAGE = 'image';
    case ARCHIVE = 'archive';
    case PRESENTATION = 'presentation';
    case OTHER = 'other';
}
