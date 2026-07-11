<?php

namespace App\Services\Stocktake;

use App\Enums\InventoryCheckStatus;
use App\Models\Stocktake\InventoryCheck;
use App\Models\Stocktake\InventoryFreeze;
use App\Repositories\Contracts\Stocktake\InventoryFreezeRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class InventoryFreezeService
{
    public function __construct(
        private InventoryFreezeRepositoryInterface $freezeRepository,
    ) {}

    /**
     * Đóng băng kho (hoặc khu vực) để kiểm kê. Chỉ cho phép khi phiếu đang
     * InProgress và chưa có đóng băng nào còn hiệu lực.
     */
    public function freeze(InventoryCheck $inventoryCheck, int $freezeScope): InventoryFreeze
    {
        if ($inventoryCheck->status !== InventoryCheckStatus::InProgress) {
            throw new \DomainException('Chỉ có thể đóng băng kho khi phiếu đang ở trạng thái Đang kiểm kê.');
        }

        if ($this->freezeRepository->hasActiveFreeze($inventoryCheck)) {
            throw new \DomainException('Phiếu kiểm kê này đang có một đóng băng còn hiệu lực.');
        }

        return $this->freezeRepository->create([
            'check_id'     => $inventoryCheck->id,
            'warehouse_id' => $inventoryCheck->warehouse_id,
            'check_type'   => $freezeScope,
            'frozen_by'    => Auth::id(),
            'frozen_at'    => now(),
            'unfrozen_at'  => null,
        ]);
    }

    /**
     * Mở đóng băng — cho phép giao dịch nhập/xuất trở lại trên phạm vi tương ứng.
     */
    public function unfreeze(InventoryFreeze $freeze): InventoryFreeze
    {
        if (! $freeze->isActive()) {
            throw new \DomainException('Đóng băng này đã được mở trước đó.');
        }

        return $this->freezeRepository->unfreeze($freeze);
    }
}
