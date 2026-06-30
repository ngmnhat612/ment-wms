<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PutawayRule\StorePutawayRuleRequest;
use App\Http\Requests\Master\PutawayRule\UpdatePutawayRuleRequest;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\PutawayRule;
use App\Models\Warehouse;
use App\Services\PutawayRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PutawayRuleController extends Controller
{
    public function __construct(
        private readonly PutawayRuleService $putawayRuleService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PutawayRule::class);

        $filters = $request->only(['search', 'apply_on', 'status', 'sort', 'dir']);

        $rules       = $this->putawayRuleService->search($filters);
        $totalCount  = $this->putawayRuleService->totalCount();
        $activeCount = $this->putawayRuleService->activeCount();

        $products   = Product::where('status', 1)->orderBy('code')->get(['id', 'code', 'name']);
        $categories = Category::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $locations = Location::where('status', 1)
            ->internal()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $defaultWarehouse = Warehouse::where('status', 1)->orderBy('id')->first(['id', 'code', 'name']);

        return view('master.putaway-rule.index', compact(
            'rules', 'totalCount', 'activeCount',
            'products', 'categories', 'locations', 'defaultWarehouse'
        ));
    }

    // ===== STORE =====

    public function store(StorePutawayRuleRequest $request): RedirectResponse
    {
        Gate::authorize('create', PutawayRule::class);

        $this->putawayRuleService->create($request->validated());

        return redirect()
            ->route('master.putaway-rule.index')
            ->with('success', 'Đã thêm quy tắc thành công.');
    }

    // ===== UPDATE =====

    public function update(UpdatePutawayRuleRequest $request, PutawayRule $putaway_rule): RedirectResponse
    {
        Gate::authorize('update', $putaway_rule);

        $this->putawayRuleService->update($putaway_rule, $request->validated());

        return redirect()
            ->route('master.putaway-rule.index')
            ->with('success', 'Đã cập nhật quy tắc thành công.');
    }

    // ===== DESTROY =====

    public function destroy(PutawayRule $putaway_rule): RedirectResponse
    {
        Gate::authorize('delete', $putaway_rule);

        $this->putawayRuleService->delete($putaway_rule);

        return redirect()
            ->route('master.putaway-rule.index')
            ->with('success', 'Đã xóa quy tắc thành công.');
    }
}