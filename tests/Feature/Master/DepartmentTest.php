<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
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

    // =========================================================
    // ===== GUEST =====
    // =========================================================

    public function test_guest_cannot_view_department_index(): void
    {
        $response = $this->get(route('master.department.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_department(): void
    {
        $department = Department::factory()->create();

        $this->post(route('master.department.store'), ['name' => 'Kế toán', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->put(route('master.department.update', $department), ['code' => $department->code, 'name' => 'X', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->delete(route('master.department.destroy', $department))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_department_index(): void
    {
        $this->actingAsAdmin();
        Department::factory()->count(3)->create();

        $response = $this->get(route('master.department.index'));

        $response->assertOk();
        $response->assertViewIs('master.department.index');
    }

    public function test_admin_can_create_department_with_auto_generated_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.department.store'), [
            'name'   => 'Kế toán',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.department.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('departments', ['name' => 'Kế toán', 'code' => 'BP0001']);
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.department.store'), ['status' => 1]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('departments', 0);
    }

    public function test_store_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.department.store'), [
            'code'   => 'BP-01',
            'name'   => 'Kế toán',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_code_already_exists(): void
    {
        $this->actingAsAdmin();
        Department::factory()->create(['code' => 'BP0001']);

        $response = $this->post(route('master.department.store'), [
            'code'   => 'BP0001',
            'name'   => 'Kế toán',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_department(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create(['name' => 'Bộ phận cũ']);

        $response = $this->put(route('master.department.update', $department), [
            'code'   => $department->code,
            'name'   => 'Bộ phận mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.department.index'));
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Bộ phận mới']);
    }

    public function test_update_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create();

        $response = $this->put(route('master.department.update', $department), [
            'code'   => 'BP-02',
            'name'   => 'Bộ phận mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_update_fails_when_code_already_used_by_another_department(): void
    {
        $this->actingAsAdmin();
        Department::factory()->create(['code' => 'BP0001']);
        $department = Department::factory()->create(['code' => 'BP0002']);

        $response = $this->put(route('master.department.update', $department), [
            'code'   => 'BP0001',
            'name'   => 'Bộ phận mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_delete_unused_department(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create();

        $response = $this->delete(route('master.department.destroy', $department));

        $response->assertRedirect(route('master.department.index'));
        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }

    public function test_delete_fails_when_department_has_employees(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create();
        Employee::factory()->create(['department_id' => $department->id]);

        $response = $this->delete(route('master.department.destroy', $department));

        $response->assertRedirect(route('master.department.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'deleted_at' => null]);
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng ban "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_department(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.department.store'), [
            'name'   => 'Kế toán',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.department.index'));
        $this->assertDatabaseHas('departments', ['name' => 'Kế toán']);
    }

    public function test_quan_ly_in_kho_department_can_update_department(): void
    {
        $this->actingAsQuanLy('Kho');
        $department = Department::factory()->create(['name' => 'Bộ phận cũ']);

        $response = $this->put(route('master.department.update', $department), [
            'code'   => $department->code,
            'name'   => 'Bộ phận mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.department.index'));
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Bộ phận mới']);
    }

    public function test_quan_ly_in_kho_department_can_delete_department(): void
    {
        $this->actingAsQuanLy('Kho');
        $department = Department::factory()->create();

        $response = $this->delete(route('master.department.destroy', $department));

        $response->assertRedirect(route('master.department.index'));
        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng ban "Kho" / không có phòng ban (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_department(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.department.store'), [
            'name'   => 'Nhân sự',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_update_department(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $department = Department::factory()->create();

        $response = $this->put(route('master.department.update', $department), [
            'code'   => $department->code,
            'name'   => 'Bộ phận mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_delete_department(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $department = Department::factory()->create();

        $response = $this->delete(route('master.department.destroy', $department));

        $response->assertForbidden();
    }

    public function test_quan_ly_without_department_cannot_create_department(): void
    {
        $this->actingAsQuanLy(null);

        $response = $this->post(route('master.department.store'), [
            'name'   => 'Nhân sự',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_department(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.department.store'), [
            'name'   => 'Nhân sự',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_department(): void
    {
        $this->actingAsNhanVien();
        $department = Department::factory()->create();

        $response = $this->put(route('master.department.update', $department), [
            'code'   => $department->code,
            'name'   => 'Bộ phận mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_department(): void
    {
        $this->actingAsNhanVien();
        $department = Department::factory()->create();

        $response = $this->delete(route('master.department.destroy', $department));

        $response->assertForbidden();
    }
}
