<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ReorderRule\StoreReorderRuleRequest;
use App\Http\Requests\Master\ReorderRule\UpdateReorderRuleRequest;
use App\Models\Employee;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\ReorderRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReorderRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = ReorderRule::with(['product', 'warehouse', 'employee'])
            ->select('reorder_rules.*')
            ->selectSub(
                DB::table('stocks')
                    ->selectRaw('COALESCE(SUM(available_qty), 0)')
                    ->whereColumn('stocks.product_id', 'reorder_rules.product_id')
                    ->whereColumn('stocks.warehouse_id', 'reorder_rules.warehouse_id'),
                'current_stock'
            );

        if ($search = $request->search) {
            $query->whereHas('product', fn($p) =>
                $p->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
            );
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('reorder_rules.status', $request->status);
        }

        if ($request->below_min) {
            $query->havingRaw('current_stock < reorder_rules.min_qty');
        }

        $rules       = $query->orderBy('reorder_rules.id')->paginate(15)->withQueryString();
        $totalCount  = ReorderRule::count();
        $activeCount = ReorderRule::where('status', 1)->count();

        $belowCount = DB::table('reorder_rules')
            ->where('reorder_rules.status', 1)
            ->whereRaw('
                COALESCE((
                    SELECT SUM(available_qty)
                    FROM stocks
                    WHERE stocks.product_id          = reorder_rules.product_id
                      AND stocks.current_location_id = reorder_rules.location_id
                ), 0) < reorder_rules.min_qty
            ')
            ->count();

        $products   = Product::where('status', 1)->orderBy('code')->get(['id', 'code', 'name']);
        $warehouses = Warehouse::where('status', 1)->where('type', 1)->orderBy('name')->get(['id', 'code', 'name']);
        $employees  = Employee::where('status', 1)->orderBy('full_name')->get(['id', 'full_name']);

        return view('master.reorder-rule.index',
            compact('rules', 'totalCount', 'activeCount', 'belowCount', 'products', 'locations', 'employees'));
    }

    public function store(StoreReorderRuleRequest $request)
    {
        $this->authorize('create', ReorderRule::class);

        ReorderRule::create($request->validated());

        return redirect()->route('master.reorder-rule.index')
            ->with('success', 'Đã thêm reorder rule thành công.');
    }

    public function update(UpdateReorderRuleRequest $request, ReorderRule $reorder_rule)
    {
        $this->authorize('update', $reorder_rule);

        $reorder_rule->update($request->validated());

        return redirect()->route('master.reorder-rule.index')
            ->with('success', 'Đã cập nhật reorder rule thành công.');
    }

    public function destroy(ReorderRule $reorder_rule)
    {
        $this->authorize('delete', $reorder_rule);

        $reorder_rule->delete();

        return redirect()->route('master.reorder-rule.index')
            ->with('success', 'Đã xóa reorder rule.');
    }
}
