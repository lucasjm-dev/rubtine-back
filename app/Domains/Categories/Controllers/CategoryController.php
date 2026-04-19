<?php

namespace App\Domains\Categories\Controllers;

use App\Domains\Categories\Models\Category;
use App\Domains\Categories\Services\CategoryService;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index(CategoryService $service)
    {
        return $service->getAll();
    }

    public function subcategories(Category $category, CategoryService $service)
    {
        return $service->getSubcategories($category);
    }
}
