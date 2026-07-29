<?php

namespace App\Policies\StockMovement;

use App\Models\Master\Account;
use App\Models\StockMovement\StockIssue;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class StockIssuePolicy
{
    use HasRoleDepartmentAuthorization;

    /**
     * Mọi user đã đăng nhập đều xem được danh sách phiếu xuất.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết phiếu xuất.
     */
    public function view(Account $account, StockIssue $stockIssue): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo phiếu xuất.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được hoàn tất phiếu xuất
     * (Draft -> Completed, trừ tồn kho thật).
     */
    public function complete(Account $account, StockIssue $stockIssue): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, StockIssue $stockIssue): bool
    {
        return $this->allow($account);
    }

    public function cancel(Account $account, StockIssue $stockIssue): bool
    {
        return $this->allow($account);
    }
}