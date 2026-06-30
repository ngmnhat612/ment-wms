<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Supplier\StoreSupplierRequest;
use App\Http\Requests\Master\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Supplier::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        $suppliers   = $this->supplierService->search($filters);
        $totalCount  = $this->supplierService->totalCount();
        $activeCount = $this->supplierService->activeCount();

        return view('master.supplier.index', compact('suppliers', 'totalCount', 'activeCount'));
    }

    // ===== STORE =====

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Gate::authorize('create', Supplier::class);

        $supplier = $this->supplierService->create($request->validated());

        return redirect()
            ->route('master.supplier.index')
            ->with('success', "Đã thêm nhà cung cấp \"{$supplier->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        $this->supplierService->update($supplier, $request->validated());

        return redirect()
            ->route('master.supplier.index')
            ->with('success', "Đã cập nhật nhà cung cấp \"{$supplier->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('delete', $supplier);

        $name = $supplier->name;

        try {
            $this->supplierService->delete($supplier);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.supplier.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.supplier.index')
            ->with('success', "Đã xóa nhà cung cấp \"{$name}\" thành công.");
    }
}
