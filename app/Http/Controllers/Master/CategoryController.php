<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Category\StoreCategoryRequest;
use App\Http\Requests\Master\Category\UpdateCategoryRequest;
use App\Models\Master\Category;
use App\Services\Master\CategoryService;
use Illuminate\Http\JsonResponse;
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

        // Chặn input tìm kiếm quá dài (phòng trường hợp gọi trực tiếp qua URL,
        // bỏ qua giới hạn maxlength phía frontend)
        foreach (['search'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = mb_substr($filters[$key], 0, 200);
            }
        }

        $categories    = $this->categoryService->search($filters);
        $parentOptions = $this->categoryService->getParentOptions();
        $locations     = $this->categoryService->activeInternalLocations();

        return view('master.category.index', compact('categories', 'parentOptions', 'locations'));
    }

    // ===== STORE =====

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        try {
            $this->categoryService->create($request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.category.index')
                ->with('error', $e->getMessage());
        }

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

    // ===== CHECK CODE (AJAX, dùng lúc blur ở form Thêm/Sửa danh mục) =====

    public function checkCode(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Category::class);

        $request->validate([
            'code'       => 'required|string|max:20',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = $this->categoryService->codeExists(
            $request->string('code')->toString(),
            $request->integer('exclude_id') ?: null
        );

        return response()->json(['exists' => $exists]);
    }
}
