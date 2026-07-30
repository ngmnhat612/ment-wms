<?php

namespace App\Services\Master;

use App\Models\Master\Product;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;
use App\Repositories\Contracts\Master\UomRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Master\Category;
use App\Enums\ActiveStatus;
use App\Services\Concerns\CodeGeneratorService;

class ProductService
{
    use ChecksForeignKeyUsage;

    public function __construct(
        protected ProductRepositoryInterface $productRepository,
        protected UomRepositoryInterface $uomRepository,
        protected CodeGeneratorService $codeGeneratorService,
        protected ReorderRuleService $reorderRuleService,
        protected PutawayRuleService $putawayRuleService,
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

    /**
     * Vị trí Internal đang active — dùng cho dropdown "Vị trí đích" ở form Sản phẩm
     * (đồng bộ với PutawayRule khi tạo/sửa vật tư). Tái sử dụng PutawayRuleService
     * vì đã có sẵn dependency và cùng nguồn dữ liệu, tránh query Location trực tiếp
     * ở Controller (Rule #1).
     */
    public function activeInternalLocations(): Collection
    {
        return $this->putawayRuleService->activeInternalLocations();
    }

    // ===== COMMANDS =====

    /**
     * Tạo vật tư gốc.
     * Tự động tạo kèm ReorderRule (min=0, max=0 nếu không nhập).
     */
    public function create(array $data, ?UploadedFile $image = null): Product
    {
        $data = $this->resolveUom($data);

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

        $product = $this->productRepository->create($data);

        $this->reorderRuleService->syncForProduct(
            $product->id,
            $this->reorderRuleService->defaultWarehouseId(),
            (int) ($data['min_qty'] ?? 0),
            (int) ($data['max_qty'] ?? 0),
        );

        $this->putawayRuleService->syncForNewProduct(
            $product->id,
            (int) $data['category_id'],
            $this->reorderRuleService->defaultWarehouseId(),
            isset($data['location_id']) ? (int) $data['location_id'] : null,
        );

        return $product;
    }

    /**
     * Tạo biến thể — kế thừa category, uom, tracking, rotation, ảnh từ cha.
     * Min/Max KHÔNG kế thừa từ cha — mỗi biến thể có ReorderRule riêng.
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

        $this->reorderRuleService->syncForProduct(
            $variant->id,
            $this->reorderRuleService->defaultWarehouseId(),
            (int) ($data['min_qty'] ?? 0),
            (int) ($data['max_qty'] ?? 0),
        );

        $this->putawayRuleService->syncForNewProduct(
            $variant->id,
            (int) $data['category_id'],
            $this->reorderRuleService->defaultWarehouseId(),
            isset($data['location_id']) ? (int) $data['location_id'] : null,
        );

        return $variant;
    }

    /**
     * Cập nhật sản phẩm.
     * code và barcode là readonly sau khi tạo.
     * Đồng bộ ReorderRule theo min_qty/max_qty gửi lên (kể cả khi về 0/0, không xóa).
     */
    public function update(Product $product, array $data, ?UploadedFile $image = null, bool $removeImage = false): void
    {
        $data = $this->resolveUom($data);

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

        $this->reorderRuleService->syncForProduct(
            $product->id,
            $this->reorderRuleService->defaultWarehouseId(),
            (int) ($data['min_qty'] ?? 0),
            (int) ($data['max_qty'] ?? 0),
        );

        $this->putawayRuleService->syncForProduct(
            $product->id,
            $this->reorderRuleService->defaultWarehouseId(),
            isset($data['location_id']) ? (int) $data['location_id'] : null,
        );
    }

    /**
     * Xóa sản phẩm.
     * Ném exception nếu đang được tham chiếu bởi bất kỳ bảng nào khác
     * (bao gồm cả tồn kho — stocks.product_id có khai báo khóa ngoại nên
     * được tự động phát hiện qua guardNotInUse, không cần kiểm tra riêng).
     * Xóa ReorderRule tương ứng trước khi xóa sản phẩm.
     *
     * @throws \RuntimeException
     */
    public function delete(Product $product): void
    {
        $this->reorderRuleService->deleteForProduct($product->id);
        $this->putawayRuleService->deleteForProduct($product->id);

        $this->guardNotInUse('products', 'id', $product->id, 'Vật tư', $product->name);

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

    /**
     * Resolve 'uom_name' (ô nhập tự do ở form, datalist gợi nhớ) thành
     * 'uom_id' thật — tìm ĐVT trùng tên (không phân biệt hoa/thường), nếu
     * chưa có thì tự tạo mới (giống cơ chế "gõ mã cha tự resolve" ở biến
     * thể). Chỉ áp dụng khi request thực sự gửi uom_name (form Product
     * thường) — StoreProductVariantRequest không có field này vì biến thể
     * luôn kế thừa uom_id thẳng từ cha, không cần resolve lại.
     */
    private function resolveUom(array $data): array
    {
        if (array_key_exists('uom_name', $data)) {
            $uomName = trim((string) $data['uom_name']);
            unset($data['uom_name']);

            if ($uomName !== '') {
                $data['uom_id'] = $this->uomRepository->findOrCreateByName($uomName)->id;
            }
        }

        return $data;
    }

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

    /**
     * Kiểm tra mã MenT đã tồn tại chưa — dùng cho validate AJAX (blur) ở
     * form Thêm/Sửa vật tư & biến thể. Chuẩn hoá code giống lúc lưu (create())
     * để so sánh nhất quán (uppercase, trim).
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->productRepository->codeExists(
            strtoupper(trim($code)),
            $excludeId
        );
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
}
