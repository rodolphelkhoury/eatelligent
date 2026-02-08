<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\AttachCategoriesRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all(), 200);
    }

    public function show(Product $product)
    {
        return response()->json($product, 200);
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return response()->json($product, 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return response()->json($product, 200);
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
}
