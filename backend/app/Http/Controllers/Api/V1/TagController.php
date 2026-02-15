<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Tag;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tags = Tag::where('user_id', $user->id)->get();
        return $this->success($tags, 'Tags retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:tags,name,NULL,id,user_id,' . $user->id],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $tag = $user->tags()->create($validated);

        return $this->success($tag, 'Tag created successfully', 201);
    }

    public function destroy(Tag $tag): JsonResponse
    {
        Gate::authorize('delete', $tag);

        $tag->delete();

        return $this->success(null, 'Tag deleted successfully');
    }

    public function attachToClient(Request $request, Client $client): JsonResponse
    {
        Gate::authorize('update', $client);

        // Support both single name (for quick add) and array of IDs
        if ($request->has('name')) {
            $tagName = $request->input('name');
            /** @var User $user */
            $user = $request->user();
            
            $tag = Tag::firstOrCreate(
                ['name' => $tagName, 'user_id' => $user->id],
                ['color' => '#6366f1']
            );
            
            $client->tags()->syncWithoutDetaching([$tag->id]);
            return $this->success($tag, 'Tag attached to client');
        }

        $request->validate(['tag_ids' => 'required|array']);
        /** @var array<int, string> $tagIds */
        $tagIds = $request->input('tag_ids');
        
        $client->tags()->sync($tagIds);

        return $this->success(null, 'Tags updated for client');
    }

    public function detachFromClient(Client $client, Tag $tag): JsonResponse
    {
        Gate::authorize('update', $client);

        $client->tags()->detach($tag->id);

        return $this->success(null, 'Tag detached from client');
    }
}
