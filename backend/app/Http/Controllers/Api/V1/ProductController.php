<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Products\StoreProductRequest;
use App\Http\Requests\Api\V1\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductAnalyticsService $analyticsService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Product::where('user_id', $user->id)
            ->withCount('sales');

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->input('type'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        } else {
            // Default to showing only active products
            $query->active();
        }

        // Search via Scout if query parameter provided
        if ($request->filled('search')) {
            $searchQuery = $request->input('search');
            $productIds = Product::search($searchQuery)
                ->where('user_id', $user->id)
                ->get()
                ->pluck('id');
            $query->whereIn('id', $productIds);
        }

        $products = $query->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'from' => $products->firstItem(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'to' => $products->lastItem(),
                'total' => $products->total(),
            ],
            'links' => [
                'first' => $products->url(1),
                'last' => $products->url($products->lastPage()),
                'prev' => $products->previousPageUrl(),
                'next' => $products->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $user = Auth::user();

        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['slug'] = Str::slug($data['name']);

        // Ensure unique slug
        $baseSlug = $data['slug'];
        $counter = 1;
        while (Product::where('slug', $data['slug'])->where('user_id', $user->id)->exists()) {
            $data['slug'] = $baseSlug.'-'.$counter;
            $counter++;
        }

        $product = Product::create($data);
        $product->loadCount('sales');

        return response()->json([
            'data' => $product,
            'message' => 'Product created successfully',
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->loadCount(['sales', 'campaigns']);
        $product->load(['campaigns' => function ($query): void {
            $query->orderBy('campaigns.created_at', 'desc')->limit(5);
        }]);

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();

        // Regenerate slug if name changed
        if (isset($data['name']) && $data['name'] !== $product->name) {
            $data['slug'] = Str::slug($data['name']);

            // Ensure unique slug
            $baseSlug = $data['slug'];
            $counter = 1;
            $userId = $product->user_id;
            while (Product::where('slug', $data['slug'])
                ->where('user_id', $userId)
                ->where('id', '!=', $product->id)
                ->exists()) {
                $data['slug'] = $baseSlug.'-'.$counter;
                $counter++;
            }
        }

        $product->update($data);
        $product->loadCount('sales');

        return response()->json([
            'data' => $product,
            'message' => 'Product updated successfully',
        ]);
    }

    /**
     * Soft delete the specified product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json([
            'message' => 'Product archived successfully',
        ], 204);
    }

    /**
     * Restore a soft-deleted product.
     */
    public function restore(string $id): JsonResponse
    {
        $user = Auth::user();

        $product = Product::withTrashed()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->authorize('restore', $product);

        $product->restore();
        $product->loadCount('sales');

        return response()->json([
            'data' => $product,
            'message' => 'Product restored successfully',
        ]);
    }

    /**
     * Get sales for a specific product.
     */
    public function sales(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $query = $product->sales()
            ->with(['client', 'invoice', 'quote'])
            ->orderBy('sold_at', 'desc');

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->input('status'));
        }

        // Filter by period
        if ($request->has('from')) {
            $query->whereDate('sold_at', '>=', $request->input('from'));
        }
        if ($request->has('to')) {
            $query->whereDate('sold_at', '<=', $request->input('to'));
        }

        $sales = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'from' => $sales->firstItem(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'to' => $sales->lastItem(),
                'total' => $sales->total(),
            ],
            'links' => [
                'first' => $sales->url(1),
                'last' => $sales->url($sales->lastPage()),
                'prev' => $sales->previousPageUrl(),
                'next' => $sales->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get analytics for a specific product.
     */
    public function productAnalytics(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $from = $request->input('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : null;

        $stats = $this->analyticsService->productStats($product, $from, $to);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Get global analytics for all products.
     */
    public function globalAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();

        $from = $request->input('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : null;

        $stats = $this->analyticsService->globalStats($user, $from, $to);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
