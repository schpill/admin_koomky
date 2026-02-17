<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignTemplateController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $templates = CampaignTemplate::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return $this->success($templates, 'Campaign templates retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:email,sms'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:1'],
        ]);

        $template = $user->campaignTemplates()->create($validated);

        return $this->success($template, 'Campaign template created successfully', 201);
    }

    public function update(Request $request, CampaignTemplate $campaignTemplate): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($campaignTemplate->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:email,sms'],
            'subject' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:1'],
        ]);

        $campaignTemplate->update($validated);

        return $this->success($campaignTemplate, 'Campaign template updated successfully');
    }

    public function destroy(Request $request, CampaignTemplate $campaignTemplate): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($campaignTemplate->user_id !== $user->id) {
            abort(403);
        }

        $campaignTemplate->delete();

        return $this->success(null, 'Campaign template deleted successfully');
    }
}
