<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Location\StoreLocationRequest;
use App\Http\Requests\Master\Location\UpdateLocationRequest;
use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Location::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        $locations     = $this->locationService->search($filters);
        $totalCount    = $this->locationService->totalCount();
        $activeCount   = $this->locationService->activeCount();
        $internalCount = $this->locationService->internalCount();
        $treeRoots     = $this->locationService->getTreeRoots();
        $parentOptions = $this->locationService->getParentOptions();
        $defaultParentId = $parentOptions->first(fn ($p) => $p->isVirtual())?->id;
        $warehouses    = $this->locationService->getActiveWarehouses();

        return view('master.location.index', compact(
            'locations', 'totalCount', 'activeCount', 'internalCount',
            'parentOptions', 'treeRoots', 'warehouses', 'defaultParentId'
        ));
    }

    // ===== STORE =====

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Location::class);

        try {
            $this->locationService->create($request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.location.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.location.index')
            ->with('success', "Đã thêm vị trí \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        Gate::authorize('update', $location);

        try {
            $this->locationService->update($location, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.location.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.location.index')
            ->with('success', "Đã cập nhật vị trí \"{$location->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Location $location): RedirectResponse
    {
        Gate::authorize('delete', $location);

        $name = $location->name;

        try {
            $this->locationService->delete($location);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.location.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.location.index')
            ->with('success', "Đã xóa vị trí \"{$name}\" thành công.");
    }
}
