<?php

namespace App\Services;

class SmsCharCounter
{
    /**
     * @return array{segments:int, remaining:int, encoding:string, per_segment:int, length:int}
     */
    public function count(string $message): array
    {
        $isUnicode = preg_match('/[^\x00-\x7F]/', $message) === 1;
        $perSegment = $isUnicode ? 70 : 160;
        $length = mb_strlen($message, 'UTF-8');
        $segments = max(1, (int) ceil($length / $perSegment));
        $remaining = ($segments * $perSegment) - $length;

        return [
            'segments' => $segments,
            'remaining' => $remaining,
            'encoding' => $isUnicode ? 'unicode' : 'ascii',
            'per_segment' => $perSegment,
            'length' => $length,
        ];
    }
}
