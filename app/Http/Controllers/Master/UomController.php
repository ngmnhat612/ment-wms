<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Uom\StoreUomRequest;
use App\Http\Requests\Master\Uom\UpdateUomRequest;
use App\Models\Master\Uom;
use App\Services\Master\UomService;
use Illuminate\Http\JsonResponse;
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

        // Chặn input tìm kiếm quá dài (phòng trường hợp gọi trực tiếp qua URL,
        // bỏ qua giới hạn maxlength phía frontend)
        foreach (['search'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = mb_substr($filters[$key], 0, 200);
            }
        }

        $uoms        = $this->uomService->search($filters);
        $totalCount  = $this->uomService->totalCount();
        $activeCount = $this->uomService->activeCount();

        return view('master.uom.index', compact('uoms', 'totalCount', 'activeCount'));
    }

    // ===== STORE =====

    public function store(StoreUomRequest $request): RedirectResponse
    {
        Gate::authorize('create', Uom::class);

        try {
            $this->uomService->create($request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.uom.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.uom.index')
            ->with('success', "Đã thêm đơn vị tính \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateUomRequest $request, Uom $uom): RedirectResponse
    {
        Gate::authorize('update', $uom);

        try {
            $this->uomService->update($uom, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.uom.index')
                ->with('error', $e->getMessage());
        }

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

    // ===== CHECK CODE (AJAX, dùng lúc blur ở form Thêm/Sửa) =====

    public function checkCode(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Uom::class);

        $request->validate([
            'code'       => 'required|string|max:20',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = $this->uomService->codeExists(
            $request->string('code')->toString(),
            $request->integer('exclude_id') ?: null
        );

        return response()->json(['exists' => $exists]);
    }
}
