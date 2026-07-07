<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Supplier;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
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

    public function test_guest_cannot_view_supplier_index(): void
    {
        $response = $this->get(route('master.supplier.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $this->post(route('master.supplier.store'), ['name' => 'Công ty A', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->put(route('master.supplier.update', $supplier), ['code' => $supplier->code, 'name' => 'X', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->delete(route('master.supplier.destroy', $supplier))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_supplier_index(): void
    {
        $this->actingAsAdmin();
        Supplier::factory()->count(3)->create();

        $response = $this->get(route('master.supplier.index'));

        $response->assertOk();
        $response->assertViewIs('master.supplier.index');
    }

    public function test_admin_can_create_supplier_with_auto_generated_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.supplier.store'), [
            'name'   => 'Công ty A',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.supplier.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('suppliers', ['name' => 'Công ty A', 'code' => 'NCC0001']);
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.supplier.store'), ['status' => 1]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_store_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.supplier.store'), [
            'code'   => 'NCC-01',
            'name'   => 'Công ty A',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_code_already_exists(): void
    {
        $this->actingAsAdmin();
        Supplier::factory()->create(['code' => 'NCC0001']);

        $response = $this->post(route('master.supplier.store'), [
            'code'   => 'NCC0001',
            'name'   => 'Công ty A',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_email_is_invalid(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.supplier.store'), [
            'name'   => 'Công ty A',
            'email'  => 'khong-phai-email',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_create_supplier_with_full_optional_fields(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.supplier.store'), [
            'name'     => 'Công ty A',
            'tax_code' => '0312345678',
            'phone'    => '0909000111',
            'email'    => 'lienhe@congtya.com',
            'address'  => '123 Đường ABC, Q.1',
            'note'     => 'Nhà cung cấp thân thiết',
            'status'   => 1,
        ]);

        $response->assertRedirect(route('master.supplier.index'));
        $this->assertDatabaseHas('suppliers', [
            'name'     => 'Công ty A',
            'tax_code' => '0312345678',
            'email'    => 'lienhe@congtya.com',
        ]);
    }

    public function test_admin_can_update_supplier(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create(['name' => 'Công ty cũ']);

        $response = $this->put(route('master.supplier.update', $supplier), [
            'code'   => $supplier->code,
            'name'   => 'Công ty mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.supplier.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Công ty mới']);
    }

    public function test_update_keeps_existing_code_when_code_is_omitted(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create(['code' => 'NCC0009']);

        $response = $this->put(route('master.supplier.update', $supplier), [
            'name'   => 'Công ty mới',
            'status' => 1,
        ]);

        $response->assertSessionDoesntHaveErrors('code');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'code' => 'NCC0009']);
    }

    public function test_update_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();

        $response = $this->put(route('master.supplier.update', $supplier), [
            'code'   => 'NCC-02',
            'name'   => 'Công ty mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_update_fails_when_code_already_used_by_another_supplier(): void
    {
        $this->actingAsAdmin();
        Supplier::factory()->create(['code' => 'NCC0001']);
        $supplier = Supplier::factory()->create(['code' => 'NCC0002']);

        $response = $this->put(route('master.supplier.update', $supplier), [
            'code'   => 'NCC0001',
            'name'   => 'Công ty mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_update_fails_when_email_is_invalid(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();

        $response = $this->put(route('master.supplier.update', $supplier), [
            'code'   => $supplier->code,
            'name'   => 'Công ty mới',
            'email'  => 'khong-phai-email',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_delete_unused_supplier(): void
    {
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('master.supplier.destroy', $supplier));

        $response->assertRedirect(route('master.supplier.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng ban "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_supplier(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.supplier.store'), [
            'name'   => 'Công ty A',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.supplier.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'Công ty A']);
    }

    public function test_quan_ly_in_kho_department_can_update_supplier(): void
    {
        $this->actingAsQuanLy('Kho');
        $supplier = Supplier::factory()->create(['name' => 'Công ty cũ']);

        $response = $this->put(route('master.supplier.update', $supplier), [
            'code'   => $supplier->code,
            'name'   => 'Công ty mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.supplier.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Công ty mới']);
    }

    public function test_quan_ly_in_kho_department_can_delete_supplier(): void
    {
        $this->actingAsQuanLy('Kho');
        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('master.supplier.destroy', $supplier));

        $response->assertRedirect(route('master.supplier.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng ban "Kho" / không có phòng ban (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_supplier(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.supplier.store'), [
            'name'   => 'Công ty A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_update_supplier(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $supplier = Supplier::factory()->create();

        $response = $this->put(route('master.supplier.update', $supplier), [
            'code'   => $supplier->code,
            'name'   => 'Công ty mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_delete_supplier(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('master.supplier.destroy', $supplier));

        $response->assertForbidden();
    }

    public function test_quan_ly_without_department_cannot_create_supplier(): void
    {
        $this->actingAsQuanLy(null);

        $response = $this->post(route('master.supplier.store'), [
            'name'   => 'Công ty A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_supplier(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.supplier.store'), [
            'name'   => 'Công ty A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_supplier(): void
    {
        $this->actingAsNhanVien();
        $supplier = Supplier::factory()->create();

        $response = $this->put(route('master.supplier.update', $supplier), [
            'code'   => $supplier->code,
            'name'   => 'Công ty mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_supplier(): void
    {
        $this->actingAsNhanVien();
        $supplier = Supplier::factory()->create();

        $response = $this->delete(route('master.supplier.destroy', $supplier));

        $response->assertForbidden();
    }
}
