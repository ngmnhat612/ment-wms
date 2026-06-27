<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Location\StoreLocationRequest;
use App\Http\Requests\Master\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::with(['parent', 'warehouse']);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                  ->orWhere('code',    'like', "%{$search}%");
            });
        }

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->parent_id === 'root') {
            $query->whereNull('parent_id');
        } elseif ($request->parent_id) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $locations     = $query->orderBy('type')->orderBy('code')->paginate(20)->withQueryString();
        $totalCount    = Location::count();
        $activeCount   = Location::where('status', 1)->count();
        $internalCount = Location::where('type', 1)->count();
        $treeRoots     = $this->buildTree();
        $parentOptions = Location::where('type', 1)->where('status', 1)->orderBy('code')->get();
        $warehouses    = Warehouse::where('status', 1)->orderBy('name')->get();

        return view('master.location.index', compact(
            'locations', 'totalCount', 'activeCount', 'internalCount',
            'parentOptions', 'treeRoots', 'warehouses'
        ));
    }

    public function store(StoreLocationRequest $request)
    {
        $this->authorize('create', Location::class);

        // Virtual location không có parent
        if ($request->type == 2 && $request->parent_id) {
            return redirect()->route('master.location.index')
                ->with('error', 'Vị trí ảo (Virtual) không thể có vị trí cha.');
        }

        Location::create([
            'code'           => strtoupper(trim($request->code)),
            'name'           => $request->name,
            'warehouse_id'   => $request->warehouse_id,
            'type'           => $request->type,
            'parent_id'      => ($request->type == 1) ? ($request->parent_id ?: null) : null,
            'status'         => $request->status,
        ]);

        return redirect()->route('master.location.index')
            ->with('success', "Đã thêm vị trí \"{$request->name}\" thành công.");
    }

    public function update(UpdateLocationRequest $request, Location $location)
    {
        $this->authorize('update', $location);

        if ($request->parent_id) {
            $descendantIds = $location->getDescendantIds();
            if ($request->parent_id == $location->id || in_array($request->parent_id, $descendantIds)) {
                return redirect()->route('master.location.index')
                    ->with('error', 'Không thể chọn vị trí con làm vị trí cha.');
            }
        }

        $location->update([
            'code'           => strtoupper(trim($request->code)),
            'name'           => $request->name,
            'warehouse_id'   => $request->warehouse_id,
            'type'           => $request->type,
            'parent_id'      => ($request->type == 1) ? ($request->parent_id ?: null) : null,
            'status'         => $request->status,
        ]);

        return redirect()->route('master.location.index')
            ->with('success', "Đã cập nhật vị trí \"{$location->name}\" thành công.");
    }

    public function destroy(Location $location)
    {
        $this->authorize('delete', $location);

        if ($location->hasChildren()) {
            return redirect()->route('master.location.index')
                ->with('error', "Không thể xóa \"{$location->name}\" vì có vị trí con.");
        }

        if ($location->hasStock()) {
            return redirect()->route('master.location.index')
                ->with('error', "Không thể xóa \"{$location->name}\" vì đang có tồn kho.");
        }

        // Vị trí hệ thống gắn với kho — không được xóa
        if ($location->isRootLocation()) {
            return redirect()->route('master.location.index')
                ->with('error', "Không thể xóa vị trí gốc của kho \"{$location->name}\".");
        }

        $name = $location->name;
        $location->delete();

        return redirect()->route('master.location.index')
            ->with('success', "Đã xóa vị trí \"{$name}\" thành công.");
    }

    // ── Private ────────────────────────────────────────────────────────────

    private function buildTree(): \Illuminate\Support\Collection
    {
        $all = Location::with('children')->orderBy('type')->orderBy('code')->get();

        return $all->whereNull('parent_id')->values();
    }
}
