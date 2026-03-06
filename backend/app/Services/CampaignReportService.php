<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignLinkClick;
use App\Models\CampaignRecipient;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignReportService
{
    public function __construct(private readonly CampaignAnalyticsService $campaignAnalyticsService) {}

    /**
     * @return array<string, mixed>
     */
    public function getFullReport(Campaign $campaign): array
    {
        $analytics = $this->campaignAnalyticsService->forCampaign($campaign);
        /** @var Collection<int, CampaignRecipient> $recipients */
        $recipients = $campaign->recipients()->orderBy('created_at')->get();
        /** @var Collection<int, CampaignLinkClick> $clicks */
        $clicks = CampaignLinkClick::query()
            ->where('campaign_id', $campaign->id)
            ->orderBy('clicked_at')
            ->get();

        $summary = [
            'sent' => (int) ($analytics['sent_count'] ?? 0),
            'delivered' => (int) ($analytics['delivered_count'] ?? 0),
            'opened' => (int) ($analytics['opened_count'] ?? 0),
            'clicked' => (int) ($analytics['clicked_count'] ?? 0),
            'bounced' => (int) ($analytics['bounced_count'] ?? 0),
            'unsubscribed' => (int) ($analytics['unsubscribed_count'] ?? 0),
            'open_rate' => (float) ($analytics['open_rate'] ?? 0),
            'click_rate' => (float) ($analytics['click_rate'] ?? 0),
            'ctor' => (int) ($analytics['opened_count'] ?? 0) > 0
                ? round((((int) ($analytics['clicked_count'] ?? 0)) / ((int) $analytics['opened_count'])) * 100, 2)
                : 0.0,
        ];

        return [
            'summary' => $summary,
            'links' => $this->campaignAnalyticsService->getLinkStats($campaign)->values()->all(),
            'timeline' => $this->buildTimeline($recipients, $clicks),
            'recipients' => $recipients->map(fn (CampaignRecipient $recipient): array => [
                'date' => $recipient->sent_at?->toDateString() ?? $recipient->created_at?->toDateString(),
                'email' => $recipient->email,
                'status' => $recipient->status,
                'opened_at' => $recipient->opened_at?->toIso8601String(),
                'clicked_at' => $recipient->clicked_at?->toIso8601String(),
                'bounce_type' => $recipient->bounce_type,
                'unsubscribed_at' => $recipient->status === 'unsubscribed' ? $recipient->updated_at?->toIso8601String() : null,
            ])->values()->all(),
        ];
    }

    public function exportCsv(Campaign $campaign): StreamedResponse
    {
        $report = $this->getFullReport($campaign);

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['date', 'email', 'status', 'opened_at', 'clicked_at', 'bounce_type', 'unsubscribed_at']);
            foreach ($report['recipients'] as $recipient) {
                fputcsv($output, [
                    $recipient['date'],
                    $recipient['email'],
                    $recipient['status'],
                    $recipient['opened_at'],
                    $recipient['clicked_at'],
                    $recipient['bounce_type'],
                    $recipient['unsubscribed_at'],
                ]);
            }

            fclose($output);
        }, 'campaign-report-'.$campaign->id.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Campaign $campaign): \Illuminate\Http\Response
    {
        $report = $this->getFullReport($campaign);
        $html = view('reports.campaign-report', [
            'campaign' => $campaign,
            'report' => $report,
        ])->render();

        $pdf = $this->renderSimplePdf(strip_tags($html));

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="campaign-report-'.$campaign->id.'.pdf"',
        ]);
    }

    /**
     * @param  Collection<int, CampaignRecipient>  $recipients
     * @param  Collection<int, CampaignLinkClick>  $clicks
     * @return array<int, array{date:string,opens:int,clicks:int}>
     */
    private function buildTimeline(Collection $recipients, Collection $clicks): array
    {
        $timeline = [];

        foreach ($recipients as $recipient) {
            if ($recipient->opened_at instanceof CarbonInterface) {
                $date = $recipient->opened_at->toDateString();
                $timeline[$date] ??= ['date' => $date, 'opens' => 0, 'clicks' => 0];
                $timeline[$date]['opens']++;
            }
        }

        foreach ($clicks as $click) {
            if ($click->clicked_at instanceof CarbonInterface) {
                $date = $click->clicked_at->toDateString();
                $timeline[$date] ??= ['date' => $date, 'opens' => 0, 'clicks' => 0];
                $timeline[$date]['clicks']++;
            }
        }

        ksort($timeline);

        return array_values($timeline);
    }

    private function renderSimplePdf(string $text): string
    {
        $sanitized = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $text);
        $content = "BT /F1 12 Tf 40 760 Td ({$sanitized}) Tj ET";
        $length = strlen($content);

        return "%PDF-1.4\n"
            ."1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n"
            ."2 0 obj<< /Type /Pages /Count 1 /Kids [3 0 R] >>endobj\n"
            ."3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj\n"
            ."4 0 obj<< /Length {$length} >>stream\n{$content}\nendstream endobj\n"
            ."5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n"
            ."xref\n0 6\n0000000000 65535 f \n"
            ."0000000010 00000 n \n"
            ."0000000063 00000 n \n"
            ."0000000122 00000 n \n"
            ."0000000248 00000 n \n"
            ."0000000344 00000 n \n"
            ."trailer<< /Root 1 0 R /Size 6 >>\nstartxref\n414\n%%EOF";
    }
}
