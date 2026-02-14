<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tags = Tag::where('user_id', $request->user()->id)->get();
        return $this->success($tags, 'Tags retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $tag = $request->user()->tags()->create($validated);

        return $this->success($tag, 'Tag created successfully', 201);
    }

    public function attachToClient(Request $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        $request->validate(['tag_ids' => 'required|array']);
        
        $client->tags()->sync($request->tag_ids);

        return $this->success(null, 'Tags updated for client');
    }
}
