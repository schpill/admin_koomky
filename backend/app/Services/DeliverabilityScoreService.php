<?php

namespace App\Services;

class DeliverabilityScoreService
{
    /**
     * @return array{score:int,issues:array<int,array{severity:string,message:string}>}
     */
    public function analyze(string $subject, string $htmlContent): array
    {
        $score = 100;
        $issues = [];

        foreach (['free', 'urgent', '!!!', 'offer'] as $needle) {
            if (str_contains(mb_strtolower($subject), $needle)) {
                $score -= 10;
                $issues[] = [
                    'severity' => 'warning',
                    'message' => 'Subject contains a potential spam trigger: '.$needle,
                ];
            }
        }

        if (mb_strlen($subject) > 60) {
            $score -= 5;
            $issues[] = [
                'severity' => 'warning',
                'message' => 'Subject exceeds 60 characters.',
            ];
        }

        if ($subject !== '' && preg_match_all('/[A-Z]/', $subject) > (mb_strlen($subject) / 2)) {
            $score -= 10;
            $issues[] = [
                'severity' => 'warning',
                'message' => 'Subject uses excessive uppercase letters.',
            ];
        }

        if (! str_contains(mb_strtolower($htmlContent), 'unsubscribe') && ! str_contains(mb_strtolower($htmlContent), 'preferences')) {
            $score -= 20;
            $issues[] = [
                'severity' => 'error',
                'message' => 'Email content does not contain an unsubscribe or preferences link.',
            ];
        }

        preg_match_all('/<img\b[^>]*>/i', $htmlContent, $images);
        preg_match_all('/<img\b[^>]*alt=/i', $htmlContent, $imagesWithAlt);
        $imageCount = count($images[0] ?? []);
        $imageWithAltCount = count($imagesWithAlt[0] ?? []);

        if ($imageCount > 0 && $imageWithAltCount < $imageCount) {
            $score -= ($imageCount - $imageWithAltCount) * 2;
            $issues[] = [
                'severity' => 'info',
                'message' => 'Some images are missing alt text.',
            ];
        }

        $textLength = mb_strlen(trim(strip_tags($htmlContent)));
        if ($imageCount > 0 && $textLength / max(1, $imageCount * 100) < 0.3) {
            $score -= 10;
            $issues[] = [
                'severity' => 'warning',
                'message' => 'Text to image ratio looks low.',
            ];
        }

        preg_match_all('/href=["\']([^"\']+)["\']/i', $htmlContent, $links);
        foreach ($links[1] ?? [] as $link) {
            if (str_contains((string) $link, 'bit.ly') || str_contains((string) $link, 'tinyurl')) {
                $score -= 15;
                $issues[] = [
                    'severity' => 'error',
                    'message' => 'Email contains a suspicious link domain.',
                ];
                break;
            }
        }

        if (substr_count($htmlContent, '<') !== substr_count($htmlContent, '>')) {
            $score -= 5;
            $issues[] = [
                'severity' => 'warning',
                'message' => 'HTML structure may be malformed.',
            ];
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'issues' => $issues,
        ];
    }
}
