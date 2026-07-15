<?php

namespace App\Services\Stocktake;

use App\Enums\InventoryCheckScope;
use App\Enums\InventoryCheckStatus;
use App\Models\Stocktake\InventoryCheck;
use App\Repositories\Contracts\Stocktake\InventoryCheckRepositoryInterface;
use App\Repositories\Contracts\Stocktake\InventoryFreezeRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryCheckService
{
    public function __construct(
        private InventoryCheckRepositoryInterface $checkRepository,
        private InventoryFreezeRepositoryInterface $freezeRepository,
        private InventoryFreezeService $freezeService,
        private CodeGeneratorService $codeGenerator,
    ) {}

    /**
     * Tạo phiếu kiểm kê mới (luôn ở trạng thái Draft) và sinh sẵn các dòng
     * detail dựa trên tồn kho hiện có (bảng `stocks`), theo phạm vi đã chọn.
     */
    public function create(array $header, array $locationIds = []): InventoryCheck
    {
        return DB::transaction(function () use ($header, $locationIds) {
            $inventoryCheck = $this->checkRepository->create([
                'warehouse_id' => $header['warehouse_id'],
                'code'         => $header['code'] ?? '' ?: $this->checkRepository->generateCode(),
                'check_scope'  => $header['check_scope'],
                'check_type'   => $header['check_type'],
                'created_by'   => Auth::id(),
                'status'       => InventoryCheckStatus::Draft->value,
                'check_date'   => $header['check_date'],
                'purpose'      => $header['purpose'] ?? null,
                'note'         => $header['note'] ?? null,
            ]);

            $this->checkRepository->generateDetailsFromStock($inventoryCheck, $locationIds);

            return $inventoryCheck;
        });
    }

    public function update(InventoryCheck $inventoryCheck, array $header): InventoryCheck
    {
        $this->assertStatus($inventoryCheck, InventoryCheckStatus::Draft, 'chỉnh sửa');

        return $this->checkRepository->update($inventoryCheck, [
            'warehouse_id' => $header['warehouse_id'],
            'check_scope'  => $header['check_scope'],
            'check_type'   => $header['check_type'],
            'check_date'   => $header['check_date'],
            'purpose'      => $header['purpose'] ?? null,
            'note'         => $header['note'] ?? null,
        ]);
    }

    /**
     * Draft -> InProgress. Cho phép thủ kho bắt đầu nhập số liệu đếm.
     * Tự động đóng băng kho (theo phạm vi của phiếu) để tránh sai lệch số liệu.
     */
    public function start(InventoryCheck $inventoryCheck): InventoryCheck
    {
        $this->assertStatus($inventoryCheck, InventoryCheckStatus::Draft, 'bắt đầu kiểm kê');

        return DB::transaction(function () use ($inventoryCheck) {
            $inventoryCheck->update(['status' => InventoryCheckStatus::InProgress->value]);

            $this->freezeService->freeze($inventoryCheck, $inventoryCheck->check_scope->value);

            return $inventoryCheck->fresh();
        });
    }

    /**
     * Lưu số liệu đếm thực tế (actual_qty và/hoặc actual_location_id) cho từng dòng,
     * tùy theo loại kiểm kê (Quantity/Location/Both) mà form chỉ gửi lên field tương ứng.
     * Field không được gửi (rỗng) sẽ giữ nguyên giá trị đã lưu trước đó.
     * Chỉ cho phép khi phiếu đang InProgress.
     * $rows: mảng ['id' => detail_id, 'actual_qty' => ?float, 'actual_location_id' => ?int]
     */
    public function updateDetails(InventoryCheck $inventoryCheck, array $rows): void
    {
        $this->assertStatus($inventoryCheck, InventoryCheckStatus::InProgress, 'cập nhật số liệu đếm');

        DB::transaction(function () use ($inventoryCheck, $rows) {
            $detailIds = array_column($rows, 'id');

            $details = $inventoryCheck->details()
                ->whereIn('id', $detailIds)
                ->get()
                ->keyBy('id');

            foreach ($rows as $row) {
                $detail = $details->get($row['id']);

                if (! $detail) {
                    continue;
                }

                $actualQtyToSave = $row['actual_qty'] !== '' && $row['actual_qty'] !== null
                    ? $row['actual_qty']
                    : null;

                $actualLocationId = (($row['actual_location_id'] ?? '') !== '')
                    ? $row['actual_location_id']
                    : $detail->actual_location_id;

                $detail->update([
                    'actual_qty'         => $actualQtyToSave,
                    'actual_location_id' => $actualLocationId,
                ]);

                // diff_qty là computed column (storedAs) ở SQL Server — model
                // trong bộ nhớ KHÔNG tự cập nhật giá trị này sau update(),
                // nên phải refresh() để lấy đúng giá trị DB vừa tính lại.
                $detail->refresh();
            }
        });
    }

    /**
     * InProgress -> Completed. Chốt số liệu kiểm kê thực tế.
     * Tự động mở toàn bộ đóng băng còn hiệu lực của phiếu (nếu có).
     */
    public function complete(InventoryCheck $inventoryCheck): InventoryCheck
    {
        $this->assertStatus($inventoryCheck, InventoryCheckStatus::InProgress, 'hoàn thành kiểm kê');

        return DB::transaction(function () use ($inventoryCheck) {
            $activeFreeze = $inventoryCheck->freezes()->whereNull('unfrozen_at')->first();

            if ($activeFreeze) {
                $this->freezeRepository->unfreeze($activeFreeze);
            }

            $inventoryCheck->update([
                'status' => InventoryCheckStatus::Completed->value,
            ]);

            return $inventoryCheck->fresh();
        });
    }

    /**
     * Hủy phiếu — chỉ cho phép khi Draft hoặc InProgress.
     * Tự động mở toàn bộ đóng băng còn hiệu lực của phiếu (nếu có).
     */
    public function cancel(InventoryCheck $inventoryCheck): InventoryCheck
    {
        if (! in_array($inventoryCheck->status, [InventoryCheckStatus::Draft, InventoryCheckStatus::InProgress], true)) {
            throw new \DomainException('Chỉ có thể hủy phiếu ở trạng thái Nháp hoặc Đang kiểm kê.');
        }

        return DB::transaction(function () use ($inventoryCheck) {
            $activeFreeze = $inventoryCheck->freezes()->whereNull('unfrozen_at')->first();

            if ($activeFreeze) {
                $this->freezeRepository->unfreeze($activeFreeze);
            }

            $inventoryCheck->update(['status' => InventoryCheckStatus::Cancelled->value]);

            return $inventoryCheck->fresh();
        });
    }

    private function assertStatus(InventoryCheck $inventoryCheck, InventoryCheckStatus $expected, string $action): void
    {
        if ($inventoryCheck->status !== $expected) {
            throw new \DomainException("Chỉ có thể {$action} khi phiếu đang ở trạng thái \"{$expected->label()}\".");
        }
    }
}