<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagCollection;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

final class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request): TagCollection
    {
        $query = Tag::query()->where('user_id', Auth::id());

        // Search
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $tags = $query->orderBy('name')->get();

        return new TagCollection($tags);
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'in:blue,green,yellow,red,purple,pink,indigo'],
        ]);

        $tag = Tag::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'name' => $validated['name'],
            ],
            [
                'color' => $validated['color'] ?? $this->getRandomTagColor(),
            ]
        );

        return (new TagResource($tag))
            ->response()
            ->setStatusCode($tag->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    /**
     * Display the specified tag.
     */
    public function show(Tag $tag): TagResource
    {
        $this->authorize('view', $tag);

        return new TagResource($tag->load('clients'));
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, Tag $tag): JsonResponse
    {
        $this->authorize('update', $tag);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'in:blue,green,yellow,red,purple,pink,indigo'],
        ]);

        $tag->update($validated);

        return (new TagResource($tag->fresh()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return response()->json([
            'meta' => [
                'message' => 'Tag deleted successfully.',
            ],
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get a random tag color.
     */
    protected function getRandomTagColor(): string
    {
        $colors = ['blue', 'green', 'yellow', 'red', 'purple', 'pink', 'indigo'];
        return $colors[array_rand($colors)];
    }
}
