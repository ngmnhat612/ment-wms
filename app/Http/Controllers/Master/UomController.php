<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Uom\StoreUomRequest;
use App\Http\Requests\Master\Uom\UpdateUomRequest;
use App\Models\Uom;
use App\Services\UomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UomController extends Controller
{
    public function __construct(
        private readonly UomService $uomService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Uom::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        $uoms        = $this->uomService->search($filters);
        $totalCount  = $this->uomService->totalCount();
        $activeCount = $this->uomService->activeCount();

        return view('master.uom.index', compact('uoms', 'totalCount', 'activeCount'));
    }

    // ===== STORE =====

    public function store(StoreUomRequest $request): RedirectResponse
    {
        Gate::authorize('create', Uom::class);

        $this->uomService->create($request->validated());

        return redirect()
            ->route('master.uom.index')
            ->with('success', "Đã thêm đơn vị tính \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateUomRequest $request, Uom $uom): RedirectResponse
    {
        Gate::authorize('update', $uom);

        $this->uomService->update($uom, $request->validated());

        return redirect()
            ->route('master.uom.index')
            ->with('success', "Đã cập nhật đơn vị tính \"{$uom->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Uom $uom): RedirectResponse
    {
        Gate::authorize('delete', $uom);

        $name = $uom->name;

        try {
            $this->uomService->delete($uom);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.uom.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.uom.index')
            ->with('success', "Đã xóa đơn vị tính \"{$name}\" thành công.");
    }
}