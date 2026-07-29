<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ReorderRule\StoreReorderRuleRequest;
use App\Http\Requests\Master\ReorderRule\UpdateReorderRuleRequest;
use App\Models\Master\ReorderRule;
use App\Services\Master\ReorderRuleService;
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

        foreach (['search'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = mb_substr($filters[$key], 0, 200);
            }
        }

        $rules       = $this->reorderRuleService->search($filters);
        $totalCount  = $this->reorderRuleService->totalCount();
        $activeCount = $this->reorderRuleService->activeCount();

        // Active-only: dùng cho TẠO MỚI
        $products  = $this->reorderRuleService->activeProducts();
        $employees = $this->reorderRuleService->activeNonAdminEmployees();

        // Tất cả (kể cả Ngưng hoạt động): dùng cho CHỈNH SỬA, tránh mất lựa chọn
        // khi vật tư/người phụ trách của rule cũ đã bị Ngưng hoạt động.
        $allProducts  = $this->reorderRuleService->allProducts();
        $allEmployees = $this->reorderRuleService->allNonAdminEmployees();

        $defaultWarehouse = $this->reorderRuleService->defaultWarehouse();

        return view('master.reorder-rule.index', compact(
            'rules', 'totalCount', 'activeCount',
            'products', 'employees', 'allProducts', 'allEmployees', 'defaultWarehouse'
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
