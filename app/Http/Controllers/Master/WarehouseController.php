<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Master\Warehouse\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::with('rootLocation');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $warehouses  = $query->orderBy('code')->paginate(15)->withQueryString();
        $totalCount  = Warehouse::count();
        $activeCount = Warehouse::where('status', 1)->count();

        return view('master.warehouse.index', compact('warehouses', 'totalCount', 'activeCount'));
    }

    public function store(StoreWarehouseRequest $request)
    {
        $this->authorize('create', Warehouse::class);

        DB::transaction(function () use ($request) {
            $warehouse = Warehouse::create([
                'code'   => strtoupper(trim($request->code)),
                'name'   => $request->name,
                'note'   => $request->note,
                'status' => $request->status,
            ]);

            // Tự động tạo root location cho kho (Vị trí ảo)
            $rootLocation = $warehouse->locations()->create([
                'code'   => 'VIR-' . $warehouse->code,
                'name'   => 'Vị trí ảo — ' . $warehouse->name,
                'type'   => 2, // Virtual
                'status' => 1,
            ]);

            $warehouse->update(['root_location_id' => $rootLocation->id]);
        });

        return redirect()->route('master.warehouse.index')
            ->with('success', "Đã thêm kho \"{$request->name}\" thành công.");
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $this->authorize('update', $warehouse);

        $warehouse->update([
            'code'   => strtoupper(trim($request->code)),
            'name'   => $request->name,
            'note'   => $request->note,
            'status' => $request->status,
        ]);

        return redirect()->route('master.warehouse.index')
            ->with('success', "Đã cập nhật kho \"{$warehouse->name}\" thành công.");
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->authorize('delete', $warehouse);

        if ($warehouse->locations()->where('type', 1)->whereHas('stocks')->exists()) {
            return redirect()->route('master.warehouse.index')
                ->with('error', "Không thể xóa kho \"{$warehouse->name}\" vì đang có tồn kho.");
        }

        $name = $warehouse->name;
        $warehouse->delete();

        return redirect()->route('master.warehouse.index')
            ->with('success', "Đã xóa kho \"{$name}\" thành công.");
    }
}
