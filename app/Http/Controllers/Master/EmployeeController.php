<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Employee\StoreEmployeeRequest;
use App\Http\Requests\Master\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\DepartmentService;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService   $employeeService,
        private readonly DepartmentService $departmentService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        $filters = $request->only(['search', 'department_id', 'status']);

        $employees    = $this->employeeService->search($filters);
        $totalCount   = $this->employeeService->totalCount();
        $activeCount  = $this->employeeService->activeCount();
        $accountCount = $this->employeeService->accountCount();
        $departments  = $this->departmentService->getActive();
        $roles        = Role::pluck('name');

        return view('master.employee.index', compact(
            'employees', 'totalCount', 'activeCount', 'accountCount', 'departments', 'roles'
        ));
    }

    // ===== STORE =====

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Gate::authorize('create', Employee::class);

        $this->employeeService->create($request->validated());

        return redirect()
            ->route('master.employee.index')
            ->with('success', "Đã thêm nhân viên \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $this->employeeService->update($employee, $request->validated());

        return redirect()
            ->route('master.employee.index')
            ->with('success', "Đã cập nhật nhân viên \"{$employee->name}\" thành công.");
    }

    // ===== DESTROY =====
    public function destroy(Employee $employee): RedirectResponse
    {
        Gate::authorize('delete', $employee);

        $name       = $employee->name;
        $hadAccount = $employee->account()->exists();

        try {
            $this->employeeService->delete($employee);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.employee.index')
                ->with('error', $e->getMessage());
        }

        $message = $hadAccount
            ? "Đã xóa nhân viên \"{$name}\" và tài khoản đăng nhập liên quan."
            : "Đã xóa nhân viên \"{$name}\" thành công.";

        return redirect()
            ->route('master.employee.index')
            ->with('success', $message);
    }
}