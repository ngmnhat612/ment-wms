<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PutawayRule\StorePutawayRuleRequest;
use App\Http\Requests\Master\PutawayRule\UpdatePutawayRuleRequest;
use App\Models\Master\PutawayRule;
use App\Services\Master\PutawayRuleService;
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

        foreach (['search'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = mb_substr($filters[$key], 0, 200);
            }
        }

        $rules       = $this->putawayRuleService->search($filters);
        $totalCount  = $this->putawayRuleService->totalCount();
        $activeCount = $this->putawayRuleService->activeCount();

        // Active-only: dùng cho TẠO MỚI
        $products  = $this->putawayRuleService->activeProducts();
        $categories = $this->putawayRuleService->activeCategories();
        $locations  = $this->putawayRuleService->activeInternalLocations();

        // Tất cả (kể cả Ngừng hoạt động): dùng cho CHỈNH SỬA, tránh mất lựa chọn
        // khi vật tư/danh mục/vị trí của rule cũ đã bị Ngừng hoạt động.
        $allProducts   = $this->putawayRuleService->allProducts();
        $allCategories = $this->putawayRuleService->allCategories();
        $allLocations  = $this->putawayRuleService->allInternalLocations();

        $defaultWarehouse = $this->putawayRuleService->defaultWarehouse();

        return view('master.putaway-rule.index', compact(
            'rules', 'totalCount', 'activeCount',
            'products', 'categories', 'locations',
            'allProducts', 'allCategories', 'allLocations',
            'defaultWarehouse'
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
