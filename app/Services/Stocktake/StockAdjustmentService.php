<?php

namespace App\Services\Stocktake;

use App\Enums\DocumentStatus;
use App\Enums\InventoryCheckStatus;
use App\Models\Stocktake\InventoryCheck;
use App\Models\Stocktake\StockAdjustment;
use App\Repositories\Contracts\Stocktake\StockAdjustmentRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Inventory\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(
        private StockAdjustmentRepositoryInterface $adjustmentRepository,
        private StockService $stockService,
        private CodeGeneratorService $codeGenerator,
    ) {}

    /**
     * Tạo phiếu điều chỉnh (Draft) từ các dòng InventoryCheckDetail đã chọn
     * (phải thuộc đúng $inventoryCheck và có chênh lệch != 0).
     * KHÔNG cập nhật tồn kho ở bước này — chỉ ghi lại các dòng cần điều
     * chỉnh; tồn kho chỉ thay đổi khi complete().
     */
    public function create(InventoryCheck $inventoryCheck, array $header, array $detailIds): StockAdjustment
    {
        if ($inventoryCheck->status !== InventoryCheckStatus::Completed) {
            throw new \DomainException('Chỉ có thể tạo phiếu điều chỉnh từ phiếu kiểm kê đã Hoàn thành.');
        }

        $checkDetails = $inventoryCheck->details()
            ->whereIn('id', $detailIds)
            ->whereRaw('diff_qty != 0')
            ->get();

        if ($checkDetails->isEmpty()) {
            throw new \DomainException('Không có dòng chênh lệch hợp lệ nào được chọn để điều chỉnh.');
        }

        return DB::transaction(function () use ($inventoryCheck, $header, $checkDetails) {
            $adjustment = $this->adjustmentRepository->create([
                'warehouse_id'     => $inventoryCheck->warehouse_id,
                'code'             => $header['code'] ?? $this->adjustmentRepository->generateCode(),
                'check_id'         => $inventoryCheck->id,
                'created_by'       => Auth::id(),
                'status'           => DocumentStatus::Draft->value,
                'adjustment_date'  => $header['adjustment_date'],
                'note'             => $header['note'] ?? null,
            ]);

            $this->adjustmentRepository->createDetailsFromCheckDetails($adjustment, $checkDetails);

            return $adjustment;
        });
    }

    /**
     * Xác nhận điều chỉnh — bước DUY NHẤT thực sự cập nhật tồn kho, thông
     * qua StockService (Rule #4 — không ghi trực tiếp lên bảng `stocks`).
     * Với mỗi dòng: diff > 0 -> increase() tại vị trí thực tế;
     *               diff < 0 -> decrease() tại vị trí thực tế.
     */
    public function complete(StockAdjustment $adjustment): StockAdjustment
    {
        $this->assertDraft($adjustment);

        return DB::transaction(function () use ($adjustment) {
            foreach ($adjustment->details as $detail) {
                $diff = (float) $detail->diff_qty;

                if (abs($diff) < 0.0001) {
                    continue;
                }

                $params = [
                    'warehouse_id' => $adjustment->warehouse_id,
                    'product_id'   => $detail->product_id,
                    'location_id'  => $detail->actual_location_id ?? $detail->system_location_id,
                    'lot_id'       => $detail->lot_id,
                    'serial_id'    => $detail->serial_id,
                    'quantity'     => abs($diff),
                ];

                if ($diff > 0) {
                    $this->stockService->increase($params);
                } else {
                    $this->stockService->decrease($params);
                }
            }

            $adjustment->update([
                'status'      => DocumentStatus::Completed->value,
                'approved_by' => Auth::id(),
            ]);

            return $adjustment->fresh();
        });
    }

    /**
     * Hủy phiếu điều chỉnh — chỉ cho phép khi còn Draft (chưa cập nhật tồn kho).
     */
    public function cancel(StockAdjustment $adjustment): StockAdjustment
    {
        $this->assertDraft($adjustment);

        $adjustment->update(['status' => DocumentStatus::Cancelled->value]);

        return $adjustment->fresh();
    }

    private function assertDraft(StockAdjustment $adjustment): void
    {
        if ($adjustment->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể thao tác trên phiếu điều chỉnh ở trạng thái Nháp.');
        }
    }
}
