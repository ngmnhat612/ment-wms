<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Sn\StoreSnRequest;
use App\Http\Requests\Master\Sn\UpdateSnRequest;
use App\Models\Sn;
use App\Services\SnService;
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

        $sns = $this->snService->search($filters);

        return view('master.sn.index', compact('sns'));
    }

    // ===== STORE =====

    public function store(StoreSnRequest $request): RedirectResponse
    {
        Gate::authorize('create', Sn::class);

        $this->snService->create($request->validated());

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
}
