<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\AttachCategoriesRequest;
use App\Http\Requests\Product\AttachImageRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all(), 200);
    }

    public function show(Product $product)
    {
        return response()->json($product->load('categories'), 200);
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return response()->json($product->load('categories'), 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return response()->json($product->load('categories'), 200);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

    public function attachCategories(AttachCategoriesRequest $request, Product $product)
    {
        $categoryIds = $request->validated()['category_ids'];

        $product->categories()->syncWithoutDetaching($categoryIds);

        return response()->json([
            'message' => 'Categories attached successfully',
            'product' => $product->load('categories'),
        ], 200);
    }

    public function detachCategories(AttachCategoriesRequest $request, Product $product)
    {
        $categoryIds = $request->validated()['category_ids'];

        $product->categories()->detach($categoryIds);

        return response()->json([
            'message' => 'Categories removed successfully',
            'product' => $product->load('categories'),
        ], 200);
    }

    public function browseProducts(Request $request)
    {
        $query = Product::query()->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->with('categories')->get();

        return response()->json($products, 200);
    }

    public function attachImage(AttachImageRequest $request, Product $product)
    {
        $image = Image::findOrFail($request->validated()['image_id']);

        if ($image->owner_id !== null && $image->owner_type !== null) {
            return response()->json([
                'message' => 'This image is already attached to another owner.',
            ], 422);
        }

        if ($product->image()->exists()) {
            return response()->json([
                'message' => 'This product already has an image.',
            ], 422);
        }

        $image->owner()->associate($product);
        $image->save();

        return response()->json([
            'message' => 'Image attached successfully',
            'product' => $product->refresh(),
        ], 200);
    }

    public function detachImage(AttachImageRequest $request, Product $product)
    {
        $image = $product->images()
            ->where('id', $request->validated()['image_id'])
            ->first();

        if (! $image) {
            return response()->json([
                'message' => 'Image not found for this product',
            ], 404);
        }

        $image->owner()->dissociate();
        $image->save();

        return response()->json([
            'message' => 'Image detached successfully',
            'product' => $product->refresh(),
        ], 200);
    }
}
