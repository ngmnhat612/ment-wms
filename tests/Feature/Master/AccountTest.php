<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
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

    public function test_guest_cannot_store_update_or_destroy_account(): void
    {
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id]);

        $this->post(route('master.employee.account.store', Employee::factory()->create()), [])
            ->assertRedirect(route('login'));

        $this->put(route('master.employee.account.update', $employee), [])
            ->assertRedirect(route('login'));

        $this->delete(route('master.employee.account.destroy', $employee))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_create_account_for_employee_without_account(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();

        $response = $this->post(route('master.employee.account.store', $employee), [
            'employee_id'           => $employee->id,
            'username'              => 'nva',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'Nhân viên',
            'account_status'        => 1,
        ]);

        $response->assertRedirect(route('master.employee.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('accounts', ['employee_id' => $employee->id, 'username' => 'nva']);
        $this->assertTrue($employee->account->hasRole('Nhân viên'));
    }

    public function test_store_fails_when_employee_already_has_account(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id]);

        $response = $this->post(route('master.employee.account.store', $employee), [
            'employee_id'           => $employee->id,
            'username'              => 'nva2',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'Nhân viên',
            'account_status'        => 1,
        ]);

        // Chặn bởi unique:accounts,employee_id ở FormRequest, chưa chạm RuntimeException trong Service
        $response->assertSessionHasErrors('employee_id');
    }

    public function test_store_fails_when_password_confirmation_mismatch(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();

        $response = $this->post(route('master.employee.account.store', $employee), [
            'employee_id'           => $employee->id,
            'username'              => 'nva',
            'password'              => 'password123',
            'password_confirmation' => 'wrongpass',
            'role'                  => 'Nhân viên',
            'account_status'        => 1,
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_admin_can_update_role_and_status(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();
        $account  = Account::factory()->create(['employee_id' => $employee->id]);
        $account->assignRole('Nhân viên');

        $response = $this->put(route('master.employee.account.update', $employee), [
            'role'           => 'Quản lý',
            'account_status' => 0,
        ]);

        $response->assertRedirect(route('master.employee.index'));
        $this->assertTrue($account->fresh()->hasRole('Quản lý'));
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'status' => 0]);
    }

    public function test_admin_cannot_update_own_account(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->put(route('master.employee.account.update', $admin->employee), [
            'role'           => 'Admin',
            'account_status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_update_protected_account(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id, 'is_protected' => true]);

        $response = $this->put(route('master.employee.account.update', $employee), [
            'role'           => 'Quản lý',
            'account_status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_normal_account(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();
        $account  = Account::factory()->create(['employee_id' => $employee->id]);
        $account->assignRole('Nhân viên');

        $response = $this->delete(route('master.employee.account.destroy', $employee));

        $response->assertRedirect(route('master.employee.index'));
        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->delete(route('master.employee.account.destroy', $admin->employee));

        $response->assertForbidden();
        $this->assertDatabaseHas('accounts', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_delete_protected_account(): void
    {
        $this->actingAsAdmin();
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id, 'is_protected' => true]);

        $response = $this->delete(route('master.employee.account.destroy', $employee));

        $response->assertForbidden();
    }

    // =========================================================
    // ===== QUẢN LÝ (luôn bị từ chối — không có ngoại lệ phòng ban như Uom) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_cannot_create_account(): void
    {
        $this->actingAsQuanLy('Kho');
        $employee = Employee::factory()->create();

        $response = $this->post(route('master.employee.account.store', $employee), [
            'employee_id'           => $employee->id,
            'username'              => 'nva',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'Nhân viên',
            'account_status'        => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_cannot_update_account(): void
    {
        $this->actingAsQuanLy('Kho');
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id]);

        $response = $this->put(route('master.employee.account.update', $employee), [
            'role'           => 'Nhân viên',
            'account_status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_cannot_delete_account(): void
    {
        $this->actingAsQuanLy('Kho');
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id]);

        $response = $this->delete(route('master.employee.account.destroy', $employee));

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_account(): void
    {
        $this->actingAsNhanVien();
        $employee = Employee::factory()->create();

        $response = $this->post(route('master.employee.account.store', $employee), [
            'employee_id'           => $employee->id,
            'username'              => 'nva',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'Nhân viên',
            'account_status'        => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_account(): void
    {
        $this->actingAsNhanVien();
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id]);

        $response = $this->put(route('master.employee.account.update', $employee), [
            'role'           => 'Nhân viên',
            'account_status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_account(): void
    {
        $this->actingAsNhanVien();
        $employee = Employee::factory()->create();
        Account::factory()->create(['employee_id' => $employee->id]);

        $response = $this->delete(route('master.employee.account.destroy', $employee));

        $response->assertForbidden();
    }
}
