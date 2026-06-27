<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Brand\StoreBrandRequest;
use App\Http\Requests\Master\Brand\UpdateBrandRequest;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(
        private readonly BrandService $brandService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Brand::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        $brands = $this->brandService->search($filters);

        return view('master.brand.index', compact('brands'));
    }

    // ===== STORE =====

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Gate::authorize('create', Brand::class);

        $this->brandService->create($request->validated());

        return redirect()
            ->route('master.brand.index')
            ->with('success', "Đã thêm thương hiệu \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);

        try {
            $this->brandService->update($brand, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.brand.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.brand.index')
            ->with('success', "Đã cập nhật thương hiệu \"{$brand->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Brand $brand): RedirectResponse
    {
        Gate::authorize('delete', $brand);

        $name = $brand->name;

        try {
            $this->brandService->delete($brand);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.brand.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.brand.index')
            ->with('success', "Đã xóa thương hiệu \"{$name}\" thành công.");
    }
}