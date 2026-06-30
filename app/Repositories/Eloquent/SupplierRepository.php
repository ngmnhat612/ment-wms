<?php

namespace App\Repositories\Eloquent;

use App\Enums\ActiveStatus;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Supplier::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('tax_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $sortable = ['code', 'name', 'tax_code'];
        $sortReq  = $filters['sort'] ?? '';
        $sortDir  = in_array($filters['dir'] ?? '', ['asc', 'desc']) ? $filters['dir'] : 'desc';

        if (in_array($sortReq, $sortable)) {
            $query->orderBy($sortReq, $sortDir);
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function totalCount(): int
    {
        return Supplier::count();
    }

    public function activeCount(): int
    {
        return Supplier::where('status', ActiveStatus::Active->value)->count();
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): bool
    {
        return $supplier->update($data);
    }

    public function delete(Supplier $supplier): bool
    {
        return $supplier->delete();
    }

    public function hasStockReceipts(Supplier $supplier): bool
    {
        // TODO: chuyển sang check StockReceiptDetail khi module Inbound hoàn thiện.
        if (!\Illuminate\Support\Facades\Schema::hasTable('stock_receipt_details')) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table('stock_receipt_details')
            ->where('supplier_id', $supplier->id)
            ->exists();
    }
}