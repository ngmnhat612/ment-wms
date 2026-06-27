<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Employee\StoreEmployeeRequest;
use App\Http\Requests\Master\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('account');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $employees   = $query->orderBy('code')->paginate(15)->withQueryString();
        $totalCount  = Employee::count();
        $activeCount = Employee::where('status', 1)->count();

        return view('master.employee.index', compact(
            'employees', 'totalCount', 'activeCount'
        ));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $this->authorize('create', Employee::class);

        Employee::create([
            'code'         => strtoupper(trim($request->code)),
            'name'         => $request->name,
            'unique_name'  => $request->name . ' ' . $request->code,
            'phone_number' => $request->phone_number,
            'status'       => $request->status,
        ]);

        return redirect()->route('master.employee.index')
            ->with('success', "Đã thêm nhân viên \"{$request->full_name}\" thành công.");
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $employee->update($request->validated());

        return redirect()->route('master.employee.index')
            ->with('success', "Đã cập nhật nhân viên \"{$employee->full_name}\" thành công.");
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);

        if ($employee->account()->exists()) {
            return redirect()->route('master.employee.index')
                ->with('error', "Không thể xóa \"{$employee->full_name}\" vì đang có tài khoản đăng nhập. Hãy xóa tài khoản trước.");
        }

        $name = $employee->full_name;
        $employee->delete();

        return redirect()->route('master.employee.index')
            ->with('success', "Đã xóa nhân viên \"{$name}\" thành công.");
    }
}
