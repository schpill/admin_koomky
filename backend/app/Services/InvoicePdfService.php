<?php

namespace App\Services;

use App\Models\Invoice;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['user', 'client', 'lineItems', 'payments']);

        $html = view('pdf.invoice', [
            'invoice' => $invoice,
        ])->render();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf;
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();

            return $dompdf->output();
        }

        $fallbackText = trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));

        return $this->buildSimplePdf($fallbackText !== '' ? $fallbackText : 'Invoice '.$invoice->number);
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
