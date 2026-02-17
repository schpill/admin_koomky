<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Services\CampaignAnalyticsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CampaignAnalyticsService $analyticsService) {}

    public function show(Campaign $campaign): JsonResponse
    {
        Gate::authorize('view', $campaign);

        return $this->success($this->analyticsService->forCampaign($campaign), 'Campaign analytics retrieved successfully');
    }

    public function compare(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $ids = array_values(array_filter(array_map('trim', explode(',', (string) $request->query('ids', '')))));

        if ($ids === []) {
            return $this->success([], 'No campaigns provided for comparison');
        }

        $data = $this->analyticsService->compare($user, $ids);

        return $this->success($data, 'Campaign comparison retrieved successfully');
    }

    public function export(Campaign $campaign): StreamedResponse
    {
        Gate::authorize('view', $campaign);

        $metrics = $this->analyticsService->forCampaign($campaign);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="campaign-analytics-'.$campaign->id.'.csv"',
        ];

        return response()->stream(function () use ($metrics): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            fputcsv($output, ['metric', 'value']);
            foreach ($metrics as $key => $value) {
                if (is_array($value)) {
                    fputcsv($output, [$key, json_encode($value, JSON_UNESCAPED_UNICODE)]);
                } else {
                    fputcsv($output, [$key, (string) $value]);
                }
            }

            fclose($output);
        }, 200, $headers);
    }
}
