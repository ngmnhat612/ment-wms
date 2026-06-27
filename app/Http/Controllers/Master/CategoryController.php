<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Category\StoreCategoryRequest;
use App\Http\Requests\Master\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Category::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        $categories    = $this->categoryService->search($filters);
        $parentOptions = $this->categoryService->getParentOptions();

        return view('master.category.index', compact('categories', 'parentOptions'));
    }

    // ===== STORE =====

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $this->categoryService->create($request->validated());

        return redirect()
            ->route('master.category.index')
            ->with('success', "Đã thêm danh mục \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        try {
            $this->categoryService->update($category, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.category.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.category.index')
            ->with('success', "Đã cập nhật danh mục \"{$category->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $name = $category->name;

        try {
            $this->categoryService->delete($category);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.category.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.category.index')
            ->with('success', "Đã xóa danh mục \"{$name}\" thành công.");
    }
}