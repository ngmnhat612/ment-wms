<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function actingAsAdmin(): Account
    {
        $account = Account::factory()->create();
        $account->assignRole('Admin');
        $this->actingAs($account);
        return $account;
    }

    private function actingAsQuanLy(?string $departmentName = 'Kho'): Account
    {
        $department = $departmentName
            ? Department::create(['code' => 'KHO', 'name' => $departmentName, 'status' => 1])
            : null;

        $employee = Employee::factory()->create(['department_id' => $department?->id]);
        $account  = Account::factory()->create(['employee_id' => $employee->id]);
        $account->assignRole('Quản lý');
        $this->actingAs($account);

        return $account;
    }

    private function actingAsNhanVien(): Account
    {
        $account = Account::factory()->create();
        $account->assignRole('Nhân viên');
        $this->actingAs($account);
        return $account;
    }

    private function makeDepartment(): Department
    {
        return Department::create(['code' => 'KT', 'name' => 'Kế toán', 'status' => 1]);
    }

    // =========================================================
    // ===== GUEST =====
    // =========================================================

    public function test_guest_cannot_view_employee_index(): void
    {
        $response = $this->get(route('master.employee.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_employee(): void
    {
        $department = $this->makeDepartment();
        $employee   = Employee::factory()->create(['department_id' => $department->id]);

        $this->post(route('master.employee.store'), [
            'name'          => 'Nguyễn Văn A',
            'department_id' => $department->id,
            'status'        => 1,
        ])->assertRedirect(route('login'));

        $this->put(route('master.employee.update', $employee), [
            'name'          => 'X',
            'department_id' => $department->id,
            'status'        => 1,
        ])->assertRedirect(route('login'));

        $this->delete(route('master.employee.destroy', $employee))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_employee_index(): void
    {
        $this->actingAsAdmin();
        Employee::factory()->count(3)->create();

        $response = $this->get(route('master.employee.index'));

        $response->assertOk();
        $response->assertViewIs('master.employee.index');
    }

    public function test_admin_can_create_employee_with_auto_generated_code(): void
    {
        // Lưu ý: actingAsAdmin() tạo Account::factory(), và AccountFactory
        // mặc định tự sinh kèm 1 Employee::factory() cho employee_id, nên
        // bảng employees KHÔNG rỗng khi test này chạy. Vì vậy không thể
        // assert code cố định là "NV0001" — chỉ assert đúng định dạng NV####.
        $this->actingAsAdmin();
        $department = $this->makeDepartment();

        $response = $this->post(route('master.employee.store'), [
            'name'          => 'Nguyễn Văn A',
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertRedirect(route('master.employee.index'));
        $response->assertSessionHas('success');

        $employee = Employee::where('name', 'Nguyễn Văn A')->first();

        $this->assertNotNull($employee);
        $this->assertMatchesRegularExpression('/^NV\d{4}$/', $employee->code);
        $this->assertSame($department->id, $employee->department_id);
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAsAdmin();
        $department = $this->makeDepartment();

        $beforeCount = Employee::count();

        $response = $this->post(route('master.employee.store'), [
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame($beforeCount, Employee::count());
    }

    public function test_store_fails_without_department(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.employee.store'), [
            'name'   => 'Nguyễn Văn A',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('department_id');
    }

    public function test_store_fails_when_department_does_not_exist(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.employee.store'), [
            'name'          => 'Nguyễn Văn A',
            'department_id' => 999999,
            'status'        => 1,
        ]);

        $response->assertSessionHasErrors('department_id');
    }

    public function test_store_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();
        $department = $this->makeDepartment();

        $response = $this->post(route('master.employee.store'), [
            'code'          => 'NV-01',
            'name'          => 'Nguyễn Văn A',
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_code_already_exists(): void
    {
        $this->actingAsAdmin();
        $department = $this->makeDepartment();
        Employee::factory()->create(['code' => 'NV0001']);

        $response = $this->post(route('master.employee.store'), [
            'code'          => 'NV0001',
            'name'          => 'Nguyễn Văn A',
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_employee(): void
    {
        $this->actingAsAdmin();
        $department = $this->makeDepartment();
        $employee   = Employee::factory()->create(['name' => 'Tên cũ', 'department_id' => $department->id]);

        $response = $this->put(route('master.employee.update', $employee), [
            'name'          => 'Tên mới',
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertRedirect(route('master.employee.index'));
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'name' => 'Tên mới']);
    }

    public function test_update_fails_without_department(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();

        $response = $this->put(route('master.employee.update', $employee), [
            'name'   => 'Tên mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('department_id');
    }

    public function test_updating_employee_to_inactive_also_deactivates_linked_account(): void
    {
        $this->actingAsAdmin();
        $department = $this->makeDepartment();
        $employee   = Employee::factory()->create(['department_id' => $department->id]);
        $account    = Account::factory()->create(['employee_id' => $employee->id, 'status' => 1]);

        $response = $this->put(route('master.employee.update', $employee), [
            'name'          => $employee->name,
            'department_id' => $department->id,
            'status'        => 0,
        ]);

        $response->assertRedirect(route('master.employee.index'));
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'status' => 0]);
    }

    public function test_admin_can_delete_employee_without_account(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();

        $response = $this->delete(route('master.employee.destroy', $employee));

        $response->assertRedirect(route('master.employee.index'));
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_admin_can_delete_employee_with_account_cascades_account_deletion(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();
        $account  = Account::factory()->create(['employee_id' => $employee->id, 'is_protected' => false]);

        $response = $this->delete(route('master.employee.destroy', $employee));

        $response->assertRedirect(route('master.employee.index'));
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }

    public function test_delete_fails_when_account_is_protected(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id, 'is_protected' => true]);

        $response = $this->delete(route('master.employee.destroy', $employee));

        $response->assertRedirect(route('master.employee.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'deleted_at' => null]);
    }

    // =========================================================
    // ===== QUẢN LÝ (luôn bị từ chối, kể cả trong phòng ban "Kho") =====
    // =========================================================

    public function test_quan_ly_in_kho_department_cannot_view_employee_index(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->get(route('master.employee.index'));

        $response->assertForbidden();
    }

    public function test_quan_ly_in_kho_department_cannot_create_employee(): void
    {
        $this->actingAsQuanLy('Kho');
        $department = $this->makeDepartment();

        $response = $this->post(route('master.employee.store'), [
            'name'          => 'Nguyễn Văn A',
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_in_kho_department_cannot_update_employee(): void
    {
        $this->actingAsQuanLy('Kho');
        $department = $this->makeDepartment();
        $employee   = Employee::factory()->create(['department_id' => $department->id]);

        $response = $this->put(route('master.employee.update', $employee), [
            'name'          => 'Tên mới',
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_in_kho_department_cannot_delete_employee(): void
    {
        $this->actingAsQuanLy('Kho');
        $employee = Employee::factory()->create();

        $response = $this->delete(route('master.employee.destroy', $employee));

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_view_employee_index(): void
    {
        $this->actingAsNhanVien();

        $response = $this->get(route('master.employee.index'));

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_create_employee(): void
    {
        $this->actingAsNhanVien();
        $department = $this->makeDepartment();

        $response = $this->post(route('master.employee.store'), [
            'name'          => 'Nguyễn Văn A',
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_employee(): void
    {
        $this->actingAsNhanVien();
        $department = $this->makeDepartment();
        $employee   = Employee::factory()->create(['department_id' => $department->id]);

        $response = $this->put(route('master.employee.update', $employee), [
            'name'          => 'Tên mới',
            'department_id' => $department->id,
            'status'        => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_employee(): void
    {
        $this->actingAsNhanVien();
        $employee = Employee::factory()->create();

        $response = $this->delete(route('master.employee.destroy', $employee));

        $response->assertForbidden();
    }
}
