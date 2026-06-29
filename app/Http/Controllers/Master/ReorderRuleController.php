<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ReorderRule\StoreReorderRuleRequest;
use App\Http\Requests\Master\ReorderRule\UpdateReorderRuleRequest;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ReorderRule;
use App\Models\Warehouse;
use App\Services\ReorderRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReorderRuleController extends Controller
{
    public function __construct(
        private readonly ReorderRuleService $reorderRuleService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ReorderRule::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        $rules       = $this->reorderRuleService->search($filters);
        $totalCount  = $this->reorderRuleService->totalCount();
        $activeCount = $this->reorderRuleService->activeCount();

        $products         = Product::where('status', 1)->orderBy('code')->get(['id', 'code', 'name']);
        $employees = Employee::where('status', 1)
            ->whereDoesntHave('account', function ($q) {
                $q->role('Admin');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        $defaultWarehouse = Warehouse::where('status', 1)->orderBy('id')->first(['id', 'code', 'name']);

        return view('master.reorder-rule.index', compact(
            'rules', 'totalCount', 'activeCount',
            'products', 'employees', 'defaultWarehouse'
        ));
    }

    // ===== STORE =====

    public function store(StoreReorderRuleRequest $request): RedirectResponse
    {
        Gate::authorize('create', ReorderRule::class);

        $this->reorderRuleService->create($request->validated());

        return redirect()
            ->route('master.reorder-rule.index')
            ->with('success', 'Đã thêm quy tắc thành công.');
    }

    // ===== UPDATE =====

    public function update(UpdateReorderRuleRequest $request, ReorderRule $reorder_rule): RedirectResponse
    {
        Gate::authorize('update', $reorder_rule);

        $this->reorderRuleService->update($reorder_rule, $request->validated());

        return redirect()
            ->route('master.reorder-rule.index')
            ->with('success', 'Đã cập nhật quy tắc thành công.');
    }

    // ===== DESTROY =====

    public function destroy(ReorderRule $reorder_rule): RedirectResponse
    {
        Gate::authorize('delete', $reorder_rule);

        $this->reorderRuleService->delete($reorder_rule);

        return redirect()
            ->route('master.reorder-rule.index')
            ->with('success', 'Đã xóa quy tắc thành công.');
    }
}