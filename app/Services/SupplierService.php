<?php

namespace App\Services;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
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
     * Xóa nhà cung cấp.
     *
     * @throws \RuntimeException khi đang có phiếu nhập kho liên quan.
     */
    public function delete(Supplier $supplier): void
    {
        if ($this->supplierRepository->hasStockReceipts($supplier)) {
            throw new \RuntimeException(
                "Không thể xóa \"{$supplier->name}\" vì đang có phiếu nhập kho liên quan."
            );
        }

        $this->supplierRepository->delete($supplier);
    }
}