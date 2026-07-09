<?php

namespace App\Policies\Inventory;

use App\Models\Master\Account;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class StockPolicy
{
    use HasRoleDepartmentAuthorization;

    /**
     * Mọi user đã đăng nhập đều xem được danh sách tồn kho.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được chỉnh sửa vị trí nhanh
     * và xem chi tiết sê-ri (thao tác thay đổi/kiểm tra dữ liệu tồn kho).
     * Không ràng buộc theo 1 dòng Stock cụ thể vì 1 lần cập nhật
     * có thể ảnh hưởng nhiều dòng (gộp theo product + lot + vị trí).
     */
    public function update(Account $account): bool
    {
        return $this->allow($account);
    }
}