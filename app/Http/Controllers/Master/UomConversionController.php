<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UomConversion\StoreUomConversionRequest;
use App\Http\Requests\Master\UomConversion\UpdateUomConversionRequest;
use App\Models\Uom;
use App\Models\UomConversion;
use Illuminate\Http\Request;

class UomConversionController extends Controller
{
    public function index(Request $request)
    {
        $query = UomConversion::with(['fromUom', 'toUom']);

        if ($search = $request->search) {
            $query->whereHas('fromUom', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('toUom',  fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $conversions = $query->orderBy('from_uom_id')->paginate(15)->withQueryString();
        $totalCount  = UomConversion::count();
        $activeCount = UomConversion::where('status', 1)->count();
        $uoms        = Uom::where('status', 1)->orderBy('name')->get();

        return view('master.uom_conversion.index', compact(
            'conversions', 'totalCount', 'activeCount', 'uoms'
        ));
    }

    public function store(StoreUomConversionRequest $request)
    {
        $this->authorize('create', UomConversion::class);

        UomConversion::create($request->validated());

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã thêm quy đổi đơn vị thành công.');
    }

    public function update(UpdateUomConversionRequest $request, UomConversion $uom_conversion)
    {
        $this->authorize('update', $uom_conversion);

        $uom_conversion->update($request->validated());

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã cập nhật quy đổi đơn vị thành công.');
    }

    public function destroy(UomConversion $uom_conversion)
    {
        $this->authorize('delete', $uom_conversion);

        $uom_conversion->delete();

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã xóa quy đổi đơn vị thành công.');
    }
}
