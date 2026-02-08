<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\AttachProductsRequest;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::all(), 200);
    }

    public function show(Category $category)
    {
        return response()->json($category->load('products'), 200);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return response()->json($category, 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return response()->json($category, 200);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }

    public function attachProducts(AttachProductsRequest $request, Category $category)
    {
        $productIds = $request->validated()['product_ids'];

        $category->products()->syncWithoutDetaching($productIds);

        return response()->json([
            'message' => 'Products attached successfully',
            'category' => $category->load('products'),
        ], 200);
    }

    public function detachProducts(AttachProductsRequest $request, Category $category)
    {
        $productIds = $request->validated()['product_ids'];

        $category->products()->detach($productIds);

        return response()->json([
            'message' => 'Products removed successfully',
            'category' => $category->load('products'),
        ], 200);
    }
}
