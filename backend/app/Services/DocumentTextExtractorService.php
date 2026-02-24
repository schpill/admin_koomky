<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentTextExtractorService
{
    public function extract(Document $document): ?string
    {
        if (! Storage::disk($document->storage_disk)->exists($document->storage_path)) {
            return null;
        }

        $mime = $document->mime_type;

        if (in_array($mime, ['text/plain', 'text/markdown'], true)) {
            return $this->cleanText((string) Storage::disk($document->storage_disk)->get($document->storage_path));
        }

        if ($mime === 'text/html') {
            $raw = (string) Storage::disk($document->storage_disk)->get($document->storage_path);

            return $this->cleanText(strip_tags($raw));
        }

        if ($mime === 'application/pdf') {
            if (! class_exists(\Smalot\PdfParser\Parser::class)) {
                Log::warning('pdf_parser_dependency_missing', ['document_id' => $document->id]);

                return null;
            }

            $absolutePath = Storage::disk($document->storage_disk)->path($document->storage_path);
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($absolutePath);

            return $this->cleanText($pdf->getText());
        }

        if ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            if (! class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
                Log::warning('phpword_dependency_missing', ['document_id' => $document->id]);

                return null;
            }

            $absolutePath = Storage::disk($document->storage_disk)->path($document->storage_path);
            $doc = \PhpOffice\PhpWord\IOFactory::load($absolutePath);
            $text = '';
            foreach ($doc->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText()."\n";
                    }
                }
            }

            return $this->cleanText($text);
        }

        return null;
    }

    private function cleanText(string $text): ?string
    {
        $cleaned = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $cleaned !== '' ? $cleaned : null;
    }
}
