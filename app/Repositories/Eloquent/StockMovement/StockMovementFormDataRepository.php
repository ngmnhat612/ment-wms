<?php

namespace App\Repositories\Eloquent\StockMovement;

use App\Models\Master\{Account, Brand, Employee, Location, Product, Sn, Supplier, Uom, Warehouse};
use App\Enums\DocumentStatus;
use App\Models\StockRequest\StockInRequest;
use App\Repositories\Contracts\StockMovement\StockMovementFormDataRepositoryInterface;
use Illuminate\Support\Collection;

class StockMovementFormDataRepository implements StockMovementFormDataRepositoryInterface
{
    public function activeProducts(): Collection
    {
        return Product::with('uom')->where('status', 1)->orderBy('code')->get();
    }

    public function receivingLocations(): Collection
    {
        return Location::where('type', 1)->orderBy('code')->get();
    }

    public function issuingLocations(): Collection
    {
        return Location::where('type', 2)->orderBy('code')->get();
    }

    // Loại nhân viên có tài khoản role Admin ra khỏi danh sách
    // "Người nhận" (dùng chung cho form Phiếu nhập/Phiếu xuất)
    public function activeEmployees(): Collection
    {
        $adminEmployeeIds = Account::role('Admin')->pluck('employee_id')->filter();

        return Employee::active()
            ->whereNotIn('id', $adminEmployeeIds)
            ->orderBy('name')
            ->get();
    }

    public function suppliers(): Collection  { return Supplier::orderBy('name')->get(); }
    public function brands(): Collection     { return Brand::orderBy('name')->get(); }
    public function uoms(): Collection       { return Uom::orderBy('name')->get(); }
    public function warehouses(): Collection { return Warehouse::orderBy('name')->get(); }
    public function sns(): Collection        { return Sn::orderBy('code')->get(); }

    public function stockInRequests(): Collection
    {
        return StockInRequest::where('status', '!=', DocumentStatus::Cancelled)
            ->orderByDesc('id')
            ->get();
    }
}