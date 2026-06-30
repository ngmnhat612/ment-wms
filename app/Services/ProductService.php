<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Category;
use App\Enums\ActiveStatus;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CodeGeneratorService       $codeGeneratorService,
    ) {}

    // ===== QUERY =====

    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->productRepository->search($filters, $perPage);
    }

    public function totalCount(): int
    {
        return $this->productRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->productRepository->activeCount();
    }

    // ===== COMMANDS =====

    /**
     * Tạo vật tư gốc.
     */
    public function create(array $data, ?UploadedFile $image = null): Product
    {
        if (empty($data['code'])) {
            $category     = Category::findOrFail($data['category_id']);
            $prefix       = strtoupper(substr($category->code, 0, 2));
            $data['code'] = $this->codeGeneratorService->generateCode('products', 'code', $prefix);
        } else {
            $data['code'] = strtoupper(trim($data['code']));
        }

        if ($image) {
            $data['image_path'] = $this->storeImage($image, $data['name'], $data['code']);
        }

        return $this->productRepository->create($data);
    }

    /**
     * Tạo biến thể — kế thừa category, uom, tracking, rotation, ảnh từ cha.
     */
    public function createVariant(array $data, ?UploadedFile $image = null): Product
    {
        $parent = Product::where('code', strtoupper(trim($data['parent_code'])))->firstOrFail();

        $data['code'] = empty($data['code'])
            ? $this->codeGeneratorService->generateVariantCode($parent->code)
            : strtoupper(trim($data['code']));

        // Kế thừa từ cha
        $data['parent_id']           = $parent->id;
        $data['category_id']         = $parent->category_id;
        $data['uom_id']              = $parent->uom_id;
        $data['tracking_type']       = $parent->tracking_type->value;
        $data['stock_rotation']      = $parent->stock_rotation->value;
        $data['alert_before_expiry'] = $parent->alert_before_expiry;

        if ($image) {
            $data['image_path'] = $this->storeImage($image, $data['name'], $data['code']);
        } else {
            // Kế thừa ảnh nếu không upload mới
            $data['image_path'] = $parent->image_path;
        }

        unset($data['parent_code']);

        $variant = $this->productRepository->create($data);

        // Toàn bộ mã trong cùng gia đình (gốc + mọi biến thể) tự động chuyển sang Ngưng hoạt động
        $this->deactivateFamily($parent, $variant->id);

        return $variant;
    }

    /**
     * Cập nhật sản phẩm.
     * code và barcode là readonly sau khi tạo.
     */
    public function update(Product $product, array $data, ?UploadedFile $image = null, bool $removeImage = false): void
    {
        // Readonly fields — không cho phép thay đổi
        unset($data['code'], $data['barcode']);

        if ($image) {
            $this->deleteImage($product->image_path);
            $data['image_path'] = $this->storeImage($image, $data['name'], $product->code);
        }

        if ($removeImage) {
            $this->deleteImage($product->image_path);
            $data['image_path'] = null;
        }

        $this->productRepository->update($product, $data);
    }

    /**
     * Xóa sản phẩm.
     * Ném exception nếu còn tồn kho.
     *
     * @throws \RuntimeException
     */
    public function delete(Product $product): void
    {
        if ($this->productRepository->hasStock($product)) {
            throw new \RuntimeException("Không thể xóa \"{$product->name}\" vì đang có tồn kho.");
        }

        $this->productRepository->delete($product);
    }

    /**
     * Sinh mã barcode EAN-13 duy nhất.
     */
    public function generateUniqueBarcode(): string
    {
        do {
            $twelveDigits = '200' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $barcode      = $this->appendEan13Check($twelveDigits);
        } while ($this->productRepository->barcodeExists($barcode));

        return $barcode;
    }

    // ===== PRIVATE HELPERS =====

    private function storeImage(UploadedFile $file, string $productName, string $productCode): string
    {
        $extension = $file->getClientOriginalExtension();
        $slug      = Str::slug($productName);
        $filename  = $slug . '_' . strtoupper($productCode) . '.' . $extension;

        return $file->storeAs('products', $filename, 'public');
    }

    private function deleteImage(?string $imagePath): void
    {
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    /**
     * Chuyển toàn bộ mã trong cùng gia đình (mã gốc + mọi biến thể) sang Ngưng hoạt động,
     * ngoại trừ biến thể vừa được tạo.
     */
    private function deactivateFamily(Product $parent, int $excludeId): void
    {
        // Tìm về mã gốc cao nhất của gia đình (đi ngược parent_id)
        $root = $parent;
        while ($root->parent_id) {
            $root = Product::find($root->parent_id);
        }

        $familyIds = Product::where(function ($q) use ($root) {
                $q->where('id', $root->id)
                  ->orWhere('code', 'like', $root->code . '.%');
            })
            ->where('id', '!=', $excludeId)
            ->pluck('id');

        if ($familyIds->isNotEmpty()) {
            Product::whereIn('id', $familyIds)
                ->update(['status' => ActiveStatus::Inactive->value]);
        }
    }

    private function appendEan13Check(string $twelveDigits): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $twelveDigits[$i];
            $sum  += ($i % 2 === 0) ? $digit * 1 : $digit * 3;
        }
        $check = (10 - ($sum % 10)) % 10;

        return $twelveDigits . $check;
    }

    public function allRootActive(): Collection
    {
        return $this->productRepository->allRootActive();
    }

    public function findRootByCode(string $code): ?Product
    {
        return $this->productRepository->findRootByCode($code);
    }

    // public function hasNearExpiryStock(Product $product): bool
    // {
    //     if (! $product->alert_before_expiry) {
    //         return false;
    //     }

    //     $threshold = now()->addDays($product->alert_before_expiry);

    //     return $product->lots()
    //         ->whereNotNull('expiry_date')
    //         ->where('expiry_date', '<=', $threshold)
    //         ->where('status', 1)
    //         ->exists();
    // }

    // public function getTotalStock(Product $product): float
    // {
    //     return (float) $product->stocks()->sum('quantity');
    // }

    /**
     * Chuyển toàn bộ mã trong cùng gia đình (mã gốc + mọi biến thể) sang Ngưng hoạt động,
     * ngoại trừ biến thể vừa được tạo.
     */
    private function deactivateFamily(Product $parent, int $excludeId): void
    {
        // Tìm về mã gốc cao nhất của gia đình (đi ngược parent_id)
        $root = $parent;
        while ($root->parent_id) {
            $root = Product::find($root->parent_id);
        }

        $familyIds = Product::where(function ($q) use ($root) {
                $q->where('id', $root->id)
                  ->orWhere('code', 'like', $root->code . '.%');
            })
            ->where('id', '!=', $excludeId)
            ->pluck('id');

        if ($familyIds->isNotEmpty()) {
            Product::whereIn('id', $familyIds)
                ->update(['status' => ActiveStatus::Inactive->value]);
        }
    }
}
