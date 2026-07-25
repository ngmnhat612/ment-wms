<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Department\StoreDepartmentRequest;
use App\Http\Requests\Master\Department\UpdateDepartmentRequest;
use App\Models\Master\Department;
use App\Services\Master\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Department::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        // Chặn input tìm kiếm quá dài (phòng trường hợp gọi trực tiếp qua URL,
        // bỏ qua giới hạn maxlength phía frontend)
        foreach (['search'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = mb_substr($filters[$key], 0, 200);
            }
        }

        $departments = $this->departmentService->search($filters);

        return view('master.department.index', compact('departments'));
    }

    // ===== STORE =====

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Department::class);

        try {
            $this->departmentService->create($request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.department.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.department.index')
            ->with('success', "Đã thêm bộ phận \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        Gate::authorize('update', $department);

        try {
            $this->departmentService->update($department, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.department.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.department.index')
            ->with('success', "Đã cập nhật bộ phận \"{$department->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Department $department): RedirectResponse
    {
        Gate::authorize('delete', $department);

        $name = $department->name;

        try {
            $this->departmentService->delete($department);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.department.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.department.index')
            ->with('success', "Đã xóa bộ phận \"{$name}\" thành công.");
    }

    // ===== CHECK CODE (AJAX, dùng lúc blur ở form Thêm/Sửa) =====

    public function checkCode(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Department::class);

        $request->validate([
            'code'       => 'required|string|max:20',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = $this->departmentService->codeExists(
            $request->string('code')->toString(),
            $request->integer('exclude_id') ?: null
        );

        return response()->json(['exists' => $exists]);
    }
}
