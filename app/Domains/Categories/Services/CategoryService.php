<?php

namespace App\Domains\Categories\Services;

use App\Domains\Categories\Models\Category;
use App\Helpers\ApiResponse;

class CategoryService
{
    public function getAll()
    {
        $categories = Category::all();

        return ApiResponse::success($categories);
    }

    public function getSubcategories(Category $category)
    {
        $subcategories = $category->subcategories()->get();

        return ApiResponse::success($subcategories);
    }
}
