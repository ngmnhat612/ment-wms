<?php

namespace App\Services\StockRequest;

use App\Enums\DocumentStatus;
use App\Models\StockRequest\StockInRequest;
use App\Repositories\Contracts\StockRequest\StockInRequestRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockInRequestService
{
    public function __construct(
        private StockInRequestRepositoryInterface $repository,
    ) {}

    /**
     * Tạo mới phiếu yêu cầu nhập kho (trạng thái mặc định: Draft).
     */
    public function create(array $headerData, array $detailRows): StockInRequest
    {
        return DB::transaction(function () use ($headerData, $detailRows) {
            $headerData['created_by'] = $headerData['created_by'] ?? Auth::id();
            $headerData['status']     = DocumentStatus::Draft;
            $headerData['code']       = $headerData['code'] ?? $this->repository->generateCode();

            $stockInRequest = $this->repository->create($headerData);

            $this->repository->replaceDetails($stockInRequest, $detailRows);

            return $this->repository->findWithDetails($stockInRequest->id);
        });
    }

    /**
     * Cập nhật phiếu (chỉ cho phép khi đang ở trạng thái Draft).
     */
    public function update(StockInRequest $stockInRequest, array $headerData, array $detailRows): StockInRequest
    {
        if ($stockInRequest->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        return DB::transaction(function () use ($stockInRequest, $headerData, $detailRows) {
            $this->repository->update($stockInRequest, $headerData);
            $this->repository->replaceDetails($stockInRequest, $detailRows);

            return $this->repository->findWithDetails($stockInRequest->id);
        });
    }

    /**
     * Đánh dấu phiếu hoàn thành. Chỉ phiếu Draft mới được hoàn thành.
     */
    public function complete(StockInRequest $stockInRequest): StockInRequest
    {
        if ($stockInRequest->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể hoàn thành phiếu ở trạng thái Nháp.');
        }

        $this->repository->update($stockInRequest, ['status' => DocumentStatus::Completed]);

        return $stockInRequest->fresh();
    }

    /**
     * Hủy phiếu. Không cho hủy phiếu đã hoàn thành hoặc đã hủy.
     */
    public function cancel(StockInRequest $stockInRequest): StockInRequest
    {
        if ($stockInRequest->status === DocumentStatus::Cancelled) {
            throw new \DomainException('Phiếu đã được hủy trước đó.');
        }

        if ($stockInRequest->status === DocumentStatus::Completed) {
            throw new \DomainException('Không thể hủy phiếu đã hoàn thành.');
        }

        $this->repository->update($stockInRequest, ['status' => DocumentStatus::Cancelled]);

        return $stockInRequest->fresh();
    }

    /**
     * Xóa phiếu. Chỉ cho phép xóa phiếu ở trạng thái Nháp.
     */
    public function delete(StockInRequest $stockInRequest): bool
    {
        if ($stockInRequest->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể xóa phiếu ở trạng thái Nháp.');
        }

        return $this->repository->delete($stockInRequest);
    }
}
