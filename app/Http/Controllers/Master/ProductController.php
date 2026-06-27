<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Product\StoreProductRequest;
use App\Http\Requests\Master\Product\UpdateProductRequest;
use App\Http\Requests\Master\Product\StoreProductVariantRequest;
use App\Enums\ActiveStatus;
use App\Services\ProductService;
use App\Services\CategoryService;
use App\Services\UomService;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService  $productService,
        private readonly CategoryService $categoryService,
        private readonly UomService      $uomService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $filters = $request->only(['search', 'category_id', 'tracking_type', 'status', 'sort', 'dir']);

        $products    = $this->productService->search($filters);
        $totalCount  = $this->productService->totalCount();
        $activeCount = $this->productService->activeCount();
        $allProducts = $this->productService->allRootActive();
        $categories  = $this->categoryService->getActive();
        $uoms        = $this->uomService->getActive();

        return view('master.product.index', compact(
            'products', 'totalCount', 'activeCount', 'categories', 'uoms', 'allProducts'
        ));
    }

    // ===== STORE =====

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $data  = $request->safe()->except(['image']);
        $image = $request->file('image');

        try {
            $product = $this->productService->create($data, $image);
        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.product.index')
            ->with('success', "Đã thêm vật tư \"{$product->name}\" thành công.");
    }

    // ===== STORE VARIANT =====

    public function storeVariant(StoreProductVariantRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $data  = $request->safe()->except(['image']);
        $image = $request->file('image');

        try {
            $product = $this->productService->createVariant($data, $image);
        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage())
                ->with('product_form_action', 'variant');
        }

        return redirect()
            ->route('master.product.index')
            ->with('success', "Đã thêm biến thể \"{$product->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $data        = $request->safe()->except(['image', 'remove_image']);
        $image       = $request->file('image');
        $removeImage = $request->boolean('remove_image');

        try {
            $this->productService->update($product, $data, $image, $removeImage);
        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->with('product_form_action', 'update:' . $product->id);
        }

        return redirect()->route('master.product.index')
            ->with('success', "Đã cập nhật vật tư \"{$product->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        try {
            $this->productService->delete($product);
        } catch (\RuntimeException $e) {
            return redirect()->route('master.product.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('master.product.index')
            ->with('success', "Đã xóa vật tư \"{$product->name}\" thành công.");
    }

    public function find(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Product::class);
        
        $request->validate(['code' => 'required|string']);

        $product = $this->productService->findRootByCode(
            strtoupper(trim($request->code))
        );

        if (!$product) {
            return response()->json(['error' => 'Không tìm thấy vật tư gốc.'], 404);
        }

        return response()->json([
            'category_id'    => $product->category_id,
            'uom_id'         => $product->uom_id,
            'tracking_type'  => $product->tracking_type->value,
            'stock_rotation' => $product->stock_rotation->value,
            'name'           => $product->name,
            'specification'  => $product->specification ?? '',
            'image_url'      => $product->image_path ? Storage::url($product->image_path) : null,
        ]);
    }
}