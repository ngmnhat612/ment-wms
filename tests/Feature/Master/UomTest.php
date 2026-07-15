<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Product;
use App\Models\Master\Uom;
use App\Models\Master\Department;
use App\Models\Master\Employee; 
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UomTest extends TestCase
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

    public function test_guest_cannot_view_uom_index(): void
    {
        $response = $this->get(route('master.uom.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_uom(): void
    {
        $uom = Uom::factory()->create();

        $this->post(route('master.uom.store'), ['name' => 'Cái', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->put(route('master.uom.update', $uom), ['code' => $uom->code, 'name' => 'X', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->delete(route('master.uom.destroy', $uom))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_uom_index(): void
    {
        $this->actingAsAdmin();
        Uom::factory()->count(3)->create();

        $response = $this->get(route('master.uom.index'));

        $response->assertOk();
        $response->assertViewIs('master.uom.index');
    }

    public function test_admin_can_create_uom_with_auto_generated_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.uom.store'), [
            'name'   => 'Cái',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.uom.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('uoms', ['name' => 'Cái', 'code' => 'DVT0001']);
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.uom.store'), ['status' => 1]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('uoms', 0);
    }

    public function test_store_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.uom.store'), [
            'code'   => 'DVT-01',
            'name'   => 'Cái',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_code_already_exists(): void
    {
        $this->actingAsAdmin();
        Uom::factory()->create(['code' => 'DVT0001']);

        $response = $this->post(route('master.uom.store'), [
            'code'   => 'DVT0001',
            'name'   => 'Cái',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_uom(): void
    {
        $this->actingAsAdmin();
        $uom = Uom::factory()->create(['name' => 'Cái cũ']);

        $response = $this->put(route('master.uom.update', $uom), [
            'code'   => $uom->code,
            'name'   => 'Cái mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.uom.index'));
        $this->assertDatabaseHas('uoms', ['id' => $uom->id, 'name' => 'Cái mới']);
    }

    public function test_update_fails_without_code(): void
    {
        $this->actingAsAdmin();
        $uom = Uom::factory()->create();

        $response = $this->put(route('master.uom.update', $uom), [
            'name'   => 'Cái mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_delete_unused_uom(): void
    {
        $this->actingAsAdmin();
        $uom = Uom::factory()->create();

        $response = $this->delete(route('master.uom.destroy', $uom));

        $response->assertRedirect(route('master.uom.index'));
        $this->assertSoftDeleted('uoms', ['id' => $uom->id]);
    }

    public function test_delete_fails_when_uom_used_by_a_product(): void
    {
        $this->actingAsAdmin();
        $uom = Uom::factory()->create();

        Product::create([
            'code'   => 'SP0001',
            'name'   => 'Vật tư test',
            'uom_id' => $uom->id,
            'status' => 1,
        ]);

        $response = $this->delete(route('master.uom.destroy', $uom));

        $response->assertRedirect(route('master.uom.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('uoms', ['id' => $uom->id, 'deleted_at' => null]);
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng ban "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_uom(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.uom.store'), [
            'name'   => 'Cái',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.uom.index'));
        $this->assertDatabaseHas('uoms', ['name' => 'Cái']);
    }

    public function test_quan_ly_in_kho_department_can_update_uom(): void
    {
        $this->actingAsQuanLy('Kho');
        $uom = Uom::factory()->create(['name' => 'Cái cũ']);

        $response = $this->put(route('master.uom.update', $uom), [
            'code'   => $uom->code,
            'name'   => 'Cái mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.uom.index'));
        $this->assertDatabaseHas('uoms', ['id' => $uom->id, 'name' => 'Cái mới']);
    }

    public function test_quan_ly_in_kho_department_can_delete_uom(): void
    {
        $this->actingAsQuanLy('Kho');
        $uom = Uom::factory()->create();

        $response = $this->delete(route('master.uom.destroy', $uom));

        $response->assertRedirect(route('master.uom.index'));
        $this->assertSoftDeleted('uoms', ['id' => $uom->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng ban "Kho" / không có phòng ban (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_uom(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.uom.store'), [
            'name'   => 'Cái',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_update_uom(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $uom = Uom::factory()->create();

        $response = $this->put(route('master.uom.update', $uom), [
            'code'   => $uom->code,
            'name'   => 'Cái mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_delete_uom(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $uom = Uom::factory()->create();

        $response = $this->delete(route('master.uom.destroy', $uom));

        $response->assertForbidden();
    }

    public function test_quan_ly_without_department_cannot_create_uom(): void
    {
        $this->actingAsQuanLy(null);

        $response = $this->post(route('master.uom.store'), [
            'name'   => 'Cái',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_uom(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.uom.store'), [
            'name'   => 'Cái',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_uom(): void
    {
        $this->actingAsNhanVien();
        $uom = Uom::factory()->create();

        $response = $this->put(route('master.uom.update', $uom), [
            'code'   => $uom->code,
            'name'   => 'Cái mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_uom(): void
    {
        $this->actingAsNhanVien();
        $uom = Uom::factory()->create();

        $response = $this->delete(route('master.uom.destroy', $uom));

        $response->assertForbidden();
    }
}
