<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Department\StoreDepartmentRequest;
use App\Http\Requests\Master\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
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

        $departments = $this->departmentService->search($filters);

        return view('master.department.index', compact('departments'));
    }

    // ===== STORE =====

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Department::class);

        $this->departmentService->create($request->validated());

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
}
