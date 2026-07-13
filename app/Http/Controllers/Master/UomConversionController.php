<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UomConversion\StoreUomConversionRequest;
use App\Http\Requests\Master\UomConversion\UpdateUomConversionRequest;
use App\Models\Master\UomConversion;
use App\Services\Master\UomConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UomConversionController extends Controller
{
    public function __construct(
        private readonly UomConversionService $uomConversionService,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', UomConversion::class);

        $filters = $request->only(['search', 'status']);

        $conversions = $this->uomConversionService->search($filters);
        $totalCount  = $this->uomConversionService->totalCount();
        $activeCount = $this->uomConversionService->activeCount();
        $uoms        = $this->uomConversionService->activeUoms();

        return view('master.uom_conversion.index', compact(
            'conversions', 'totalCount', 'activeCount', 'uoms'
        ));
    }

    public function store(StoreUomConversionRequest $request): RedirectResponse
    {
        Gate::authorize('create', UomConversion::class);

        $this->uomConversionService->create($request->validated());

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã thêm quy đổi đơn vị thành công.');
    }

    public function update(UpdateUomConversionRequest $request, UomConversion $uom_conversion): RedirectResponse
    {
        Gate::authorize('update', $uom_conversion);

        $this->uomConversionService->update($uom_conversion, $request->validated());

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã cập nhật quy đổi đơn vị thành công.');
    }

    public function destroy(UomConversion $uom_conversion): RedirectResponse
    {
        Gate::authorize('delete', $uom_conversion);

        $this->uomConversionService->delete($uom_conversion);

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã xóa quy đổi đơn vị thành công.');
    }
}