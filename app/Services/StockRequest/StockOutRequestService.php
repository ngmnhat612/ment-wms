<?php

namespace App\Services\StockRequest;

use App\Enums\DocumentStatus;
use App\Models\StockRequest\StockOutRequest;
use App\Repositories\Contracts\StockRequest\StockOutRequestRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOutRequestService
{
    public function __construct(
        private StockOutRequestRepositoryInterface $repository,
    ) {}

    /**
     * Tạo mới phiếu yêu cầu xuất kho (trạng thái mặc định: Draft).
     */
    public function create(array $headerData, array $detailRows): StockOutRequest
    {
        return DB::transaction(function () use ($headerData, $detailRows) {
            $headerData['created_by'] = $headerData['created_by'] ?? Auth::id();
            $headerData['status']     = DocumentStatus::Draft;
            $headerData['code']       = $headerData['code'] ?? $this->repository->generateCode();

            $stockOutRequest = $this->repository->create($headerData);

            $this->repository->replaceDetails($stockOutRequest, $detailRows);

            return $this->repository->findWithDetails($stockOutRequest->id);
        });
    }

    /**
     * Cập nhật phiếu (chỉ cho phép khi đang ở trạng thái Draft).
     */
    public function update(StockOutRequest $stockOutRequest, array $headerData, array $detailRows): StockOutRequest
    {
        if ($stockOutRequest->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        return DB::transaction(function () use ($stockOutRequest, $headerData, $detailRows) {
            $this->repository->update($stockOutRequest, $headerData);
            $this->repository->replaceDetails($stockOutRequest, $detailRows);

            return $this->repository->findWithDetails($stockOutRequest->id);
        });
    }

    /**
     * Đánh dấu phiếu hoàn thành. Chỉ phiếu Draft mới được hoàn thành.
     */
    public function complete(StockOutRequest $stockOutRequest): StockOutRequest
    {
        if ($stockOutRequest->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể hoàn thành phiếu ở trạng thái Nháp.');
        }

        $this->repository->update($stockOutRequest, ['status' => DocumentStatus::Completed]);

        return $stockOutRequest->fresh();
    }

    /**
     * Hủy phiếu. Không cho hủy phiếu đã hoàn thành hoặc đã hủy.
     */
    public function cancel(StockOutRequest $stockOutRequest): StockOutRequest
    {
        if ($stockOutRequest->status === DocumentStatus::Cancelled) {
            throw new \DomainException('Phiếu đã được hủy trước đó.');
        }

        if ($stockOutRequest->status === DocumentStatus::Completed) {
            throw new \DomainException('Không thể hủy phiếu đã hoàn thành.');
        }

        $this->repository->update($stockOutRequest, ['status' => DocumentStatus::Cancelled]);

        return $stockOutRequest->fresh();
    }

    /**
     * Xóa phiếu. Chỉ cho phép xóa phiếu ở trạng thái Nháp.
     */
    public function delete(StockOutRequest $stockOutRequest): bool
    {
        if ($stockOutRequest->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể xóa phiếu ở trạng thái Nháp.');
        }

        return $this->repository->delete($stockOutRequest);
    }
}
