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
            'name' => ['required', 'string', 'max:50', 'unique:tags,name,NULL,id,user_id,'.$user->id],
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
        /** @var User $user */
        $user = $request->user();

        // Support both single name (for quick add) and array of IDs
        if ($request->has('name')) {
            $tagName = trim((string) $request->input('name'));
            if ($tagName === '') {
                return $this->error('Tag name is required', 422);
            }

            $tag = Tag::firstOrCreate(
                ['name' => $tagName, 'user_id' => $user->id],
                ['color' => '#6366f1']
            );

            $client->tags()->syncWithoutDetaching([$tag->id]);

            return $this->success($tag, 'Tag attached to client');
        }

        $validated = $request->validate([
            'tag_ids' => ['required', 'array', 'min:1'],
            'tag_ids.*' => ['required', 'string', 'uuid'],
        ]);

        /** @var array<int, string> $tagIds */
        $tagIds = array_values(array_unique($validated['tag_ids']));

        /** @var array<int, string> $ownedTagIds */
        $ownedTagIds = Tag::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->all();

        if (count($ownedTagIds) !== count($tagIds)) {
            return $this->error('One or more tags are invalid for this user', 422);
        }

        $client->tags()->sync($ownedTagIds);

        return $this->success(null, 'Tags updated for client');
    }

    public function detachFromClient(Client $client, Tag $tag): JsonResponse
    {
        Gate::authorize('update', $client);
        Gate::authorize('delete', $tag);

        $client->tags()->detach($tag->id);

        return $this->success(null, 'Tag detached from client');
    }
}
