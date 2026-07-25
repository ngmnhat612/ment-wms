<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Sn\StoreSnRequest;
use App\Http\Requests\Master\Sn\UpdateSnRequest;
use App\Models\Master\Sn;
use App\Services\Master\SnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SnController extends Controller
{
    public function __construct(
        private readonly SnService $snService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Sn::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        // Chặn input tìm kiếm quá dài (phòng trường hợp gọi trực tiếp qua URL,
        // bỏ qua giới hạn maxlength phía frontend)
        foreach (['search'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = mb_substr($filters[$key], 0, 200);
            }
        }

        $sns = $this->snService->search($filters);

        return view('master.sn.index', compact('sns'));
    }

    // ===== STORE =====

    public function store(StoreSnRequest $request): RedirectResponse
    {
        Gate::authorize('create', Sn::class);

        try {
            $this->snService->create($request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.sn.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.sn.index')
            ->with('success', "Đã thêm dự án \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateSnRequest $request, Sn $sn): RedirectResponse
    {
        Gate::authorize('update', $sn);

        try {
            $this->snService->update($sn, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.sn.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.sn.index')
            ->with('success', "Đã cập nhật dự án \"{$sn->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Sn $sn): RedirectResponse
    {
        Gate::authorize('delete', $sn);

        $name = $sn->name;

        try {
            $this->snService->delete($sn);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.sn.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.sn.index')
            ->with('success', "Đã xóa dự án \"{$name}\" thành công.");
    }

    // ===== CHECK CODE (AJAX, dùng lúc blur ở form Thêm/Sửa) =====

    public function checkCode(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Sn::class);

        $request->validate([
            'code'       => 'required|string|max:20',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = $this->snService->codeExists(
            $request->string('code')->toString(),
            $request->integer('exclude_id') ?: null
        );

        return response()->json(['exists' => $exists]);
    }
}
