<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::with(['account', 'department']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }

    public function allActive(): Collection
    {
        return Employee::active()->orderBy('name')->get();
    }

    public function countAll(): int
    {
        return Employee::count();
    }

    public function countActive(): int
    {
        return Employee::where('status', 1)->count();
    }

    public function countWithAccount(): int
    {
        return Employee::has('account')->count();
    }

    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function update(Employee $employee, array $data): bool
    {
        return $employee->update($data);
    }

    public function delete(Employee $employee): bool
    {
        return $employee->delete();
    }

}
