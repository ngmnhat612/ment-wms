<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Sn;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SnTest extends TestCase
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

    public function test_guest_cannot_view_sn_index(): void
    {
        $response = $this->get(route('master.sn.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_sn(): void
    {
        $sn = Sn::factory()->create();

        $this->post(route('master.sn.store'), ['name' => 'Dự án A', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->put(route('master.sn.update', $sn), ['code' => $sn->code, 'name' => 'X', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->delete(route('master.sn.destroy', $sn))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_sn_index(): void
    {
        $this->actingAsAdmin();
        Sn::factory()->count(3)->create();

        $response = $this->get(route('master.sn.index'));

        $response->assertOk();
        $response->assertViewIs('master.sn.index');
    }

    public function test_admin_can_create_sn_with_auto_generated_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.sn.store'), [
            'name'   => 'Dự án A',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.sn.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('sns', ['name' => 'Dự án A', 'code' => 'DA0001']);
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.sn.store'), ['status' => 1]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('sns', 0);
    }

    public function test_store_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.sn.store'), [
            'code'   => 'DA-01',
            'name'   => 'Dự án A',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_code_already_exists(): void
    {
        $this->actingAsAdmin();
        Sn::factory()->create(['code' => 'DA0001']);

        $response = $this->post(route('master.sn.store'), [
            'code'   => 'DA0001',
            'name'   => 'Dự án A',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_sn(): void
    {
        $this->actingAsAdmin();
        $sn = Sn::factory()->create(['name' => 'Dự án cũ']);

        $response = $this->put(route('master.sn.update', $sn), [
            'code'   => $sn->code,
            'name'   => 'Dự án mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.sn.index'));
        $this->assertDatabaseHas('sns', ['id' => $sn->id, 'name' => 'Dự án mới']);
    }

    /**
     * NOTE: khác với Uom (code bắt buộc khi update), StoreSnRequest/UpdateSnRequest
     * khai báo 'code' là nullable ngay cả khi update -> việc bỏ trống code KHÔNG
     * gây lỗi validate, khác hành vi của Uom.
     */
    public function test_update_does_not_fail_validation_when_code_is_omitted(): void
    {
        $this->actingAsAdmin();
        $sn = Sn::factory()->create();

        $response = $this->put(route('master.sn.update', $sn), [
            'name'   => 'Dự án mới',
            'status' => 1,
        ]);

        $response->assertSessionDoesntHaveErrors('code');
    }

    public function test_update_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();
        $sn = Sn::factory()->create();

        $response = $this->put(route('master.sn.update', $sn), [
            'code'   => 'DA-02',
            'name'   => 'Dự án mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_update_fails_when_code_already_used_by_another_sn(): void
    {
        $this->actingAsAdmin();
        Sn::factory()->create(['code' => 'DA0001']);
        $sn = Sn::factory()->create(['code' => 'DA0002']);

        $response = $this->put(route('master.sn.update', $sn), [
            'code'   => 'DA0001',
            'name'   => 'Dự án mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_delete_unused_sn(): void
    {
        $this->actingAsAdmin();
        $sn = Sn::factory()->create();

        $response = $this->delete(route('master.sn.destroy', $sn));

        $response->assertRedirect(route('master.sn.index'));
        $this->assertSoftDeleted('sns', ['id' => $sn->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng ban "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_sn(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.sn.store'), [
            'name'   => 'Dự án A',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.sn.index'));
        $this->assertDatabaseHas('sns', ['name' => 'Dự án A']);
    }

    public function test_quan_ly_in_kho_department_can_update_sn(): void
    {
        $this->actingAsQuanLy('Kho');
        $sn = Sn::factory()->create(['name' => 'Dự án cũ']);

        $response = $this->put(route('master.sn.update', $sn), [
            'code'   => $sn->code,
            'name'   => 'Dự án mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.sn.index'));
        $this->assertDatabaseHas('sns', ['id' => $sn->id, 'name' => 'Dự án mới']);
    }

    public function test_quan_ly_in_kho_department_can_delete_sn(): void
    {
        $this->actingAsQuanLy('Kho');
        $sn = Sn::factory()->create();

        $response = $this->delete(route('master.sn.destroy', $sn));

        $response->assertRedirect(route('master.sn.index'));
        $this->assertSoftDeleted('sns', ['id' => $sn->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng ban "Kho" / không có phòng ban (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_sn(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.sn.store'), [
            'name'   => 'Dự án A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_update_sn(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $sn = Sn::factory()->create();

        $response = $this->put(route('master.sn.update', $sn), [
            'code'   => $sn->code,
            'name'   => 'Dự án mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_delete_sn(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $sn = Sn::factory()->create();

        $response = $this->delete(route('master.sn.destroy', $sn));

        $response->assertForbidden();
    }

    public function test_quan_ly_without_department_cannot_create_sn(): void
    {
        $this->actingAsQuanLy(null);

        $response = $this->post(route('master.sn.store'), [
            'name'   => 'Dự án A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_sn(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.sn.store'), [
            'name'   => 'Dự án A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_sn(): void
    {
        $this->actingAsNhanVien();
        $sn = Sn::factory()->create();

        $response = $this->put(route('master.sn.update', $sn), [
            'code'   => $sn->code,
            'name'   => 'Dự án mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_sn(): void
    {
        $this->actingAsNhanVien();
        $sn = Sn::factory()->create();

        $response = $this->delete(route('master.sn.destroy', $sn));

        $response->assertForbidden();
    }
}
