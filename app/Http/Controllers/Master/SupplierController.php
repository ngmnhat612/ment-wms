<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Supplier\StoreSupplierRequest;
use App\Http\Requests\Master\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('tax_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $suppliers   = $query->orderBy('code')->paginate(15)->withQueryString();
        $totalCount  = Supplier::count();
        $activeCount = Supplier::where('status', 1)->count();

        return view('master.supplier.index', compact('suppliers', 'totalCount', 'activeCount'));
    }

    public function store(StoreSupplierRequest $request)
    {
        $this->authorize('create', Supplier::class);

        Supplier::create([
            'code'     => strtoupper(trim($request->code)),
            'name'     => $request->name,
            'tax_code' => $request->tax_code,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'address'  => $request->address,
            'status'   => $request->status,
        ]);

        return redirect()->route('master.supplier.index')
            ->with('success', "Đã thêm nhà cung cấp \"{$request->name}\" thành công.");
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $supplier->update([
            'code'     => strtoupper(trim($request->code)),
            'name'     => $request->name,
            'tax_code' => $request->tax_code,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'address'  => $request->address,
            'status'   => $request->status,
        ]);

        return redirect()->route('master.supplier.index')
            ->with('success', "Đã cập nhật nhà cung cấp \"{$supplier->name}\" thành công.");
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);

        if ($supplier->stockReceipts()->exists()) {
            return redirect()->route('master.supplier.index')
                ->with('error', "Không thể xóa \"{$supplier->name}\" vì đang có phiếu nhập kho liên quan.");
        }

        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('master.supplier.index')
            ->with('success', "Đã xóa nhà cung cấp \"{$name}\" thành công.");
    }
}
