<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

abstract class AbstractPdfService
{
    /**
     * @param  array<string, mixed>  $viewData
     */
    protected function renderPdfView(string $viewName, array $viewData, string $fallbackTitle): string
    {
        $html = View::make($viewName, $viewData)->render();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();

            return $dompdf->output();
        }

        $fallbackText = trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));

        return $this->buildSimplePdf($fallbackText !== '' ? $fallbackText : $fallbackTitle);
    }

    protected function resolveLogoDataUri(?User $user): ?string
    {
        $candidatePath = $this->resolveLogoPath($user);
        if ($candidatePath === null) {
            return null;
        }

        $cacheKey = 'pdf_logo_data_uri:'.sha1($candidatePath);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($candidatePath): ?string {
            $contents = $this->readLogoBytes($candidatePath);
            if ($contents === null) {
                return null;
            }

            $mimeType = $this->detectMimeType($candidatePath);

            return 'data:'.$mimeType.';base64,'.base64_encode($contents);
        });
    }

    private function resolveLogoPath(?User $user): ?string
    {
        if ($user !== null && is_string($user->avatar_path) && $user->avatar_path !== '') {
            return $user->avatar_path;
        }

        $defaultLogo = public_path('brand/logo.png');

        return File::exists($defaultLogo) ? $defaultLogo : null;
    }

    private function readLogoBytes(string $path): ?string
    {
        if (File::exists($path)) {
            return File::get($path);
        }

        $storage = Storage::disk(config('filesystems.default', 'local'));
        if (! $storage->exists($path)) {
            return null;
        }

        return $storage->get($path);
    }

    private function detectMimeType(string $path): string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }

    private function buildSimplePdf(string $text): string
    {
        $safeText = substr($text, 0, 180);
        $safeText = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safeText);

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>';

        $stream = "BT /F1 12 Tf 40 800 Td ({$safeText}) Tj ET";
        $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $objectNumber = $index + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= "{$objectNumber} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref'."\n";
        $pdf .= '0 '.(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        }

        $pdf .= 'trailer'."\n";
        $pdf .= '<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\n";
        $pdf .= 'startxref'."\n";
        $pdf .= $xrefOffset."\n";
        $pdf .= '%%EOF';

        return $pdf;
    }
}
