<?php

namespace App\Services\Master;

use App\Models\Master\Supplier;
use App\Repositories\Contracts\Master\SupplierRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Services\Concerns\CodeGeneratorService;

class SupplierService
{
    use ChecksForeignKeyUsage;

    public function __construct(
        private readonly SupplierRepositoryInterface $supplierRepository,
        private readonly CodeGeneratorService         $codeGeneratorService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->supplierRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->supplierRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->supplierRepository->activeCount();
    }

    // ===== WRITE =====

    /**
     * Tạo mới nhà cung cấp.
     */
    public function create(array $data): Supplier
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('suppliers', 'code', 'NCC', 4);

        return $this->supplierRepository->create([
            'code'     => $code,
            'name'     => $data['name'],
            'tax_code' => $data['tax_code'] ?? null,
            'phone'    => $data['phone'] ?? null,
            'email'    => $data['email'] ?? null,
            'address'  => $data['address'] ?? null,
            'note'     => $data['note'] ?? null,
            'status'   => $data['status'],
        ]);
    }

    /**
     * Cập nhật nhà cung cấp.
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $supplier->code;

        $this->supplierRepository->update($supplier, [
            'code'     => $code,
            'name'     => $data['name'],
            'tax_code' => $data['tax_code'] ?? null,
            'phone'    => $data['phone'] ?? null,
            'email'    => $data['email'] ?? null,
            'address'  => $data['address'] ?? null,
            'note'     => $data['note'] ?? null,
            'status'   => $data['status'],
        ]);

        return $supplier->fresh();
    }

    /**
     * Xóa cứng nhà cung cấp.
     *
     * @throws \RuntimeException khi đang có phiếu nhập kho liên quan, hoặc
     *         đang được tham chiếu bởi bất kỳ bảng nào khác.
     */
    public function delete(Supplier $supplier): void
    {
        if ($this->supplierRepository->hasStockReceipts($supplier)) {
            throw new \RuntimeException(
                "Không thể xóa \"{$supplier->name}\" vì đang có phiếu nhập kho liên quan."
            );
        }

        $this->guardNotInUse('suppliers', 'id', $supplier->id, 'Nhà cung cấp', $supplier->name);

        $this->supplierRepository->delete($supplier);
    }
}