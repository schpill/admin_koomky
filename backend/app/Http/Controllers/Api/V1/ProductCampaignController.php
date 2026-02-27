<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Products\StoreProductCampaignRequest;
use App\Models\Product;
use App\Models\Segment;
use App\Services\ProductCampaignGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductCampaignController extends Controller
{
    public function __construct(
        private readonly ProductCampaignGeneratorService $generatorService
    ) {
    }

    /**
     * Generate a campaign from a product using AI.
     */
    public function generate(StoreProductCampaignRequest $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $user = Auth::user();
        $segment = Segment::findOrFail($request->validated('segment_id'));

        // Verify segment belongs to user
        if ($segment->user_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have access to this segment',
            ], 403);
        }

        $campaign = $this->generatorService->generate($product, $segment, $user);

        return response()->json([
            'data' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'segment_id' => $campaign->segment_id,
                'template_id' => $campaign->template_id,
                'redirect_url' => "/campaigns/{$campaign->id}",
                'created_at' => $campaign->created_at,
            ],
            'message' => 'Campaign generated successfully and saved as draft',
        ], 201);
    }
}
