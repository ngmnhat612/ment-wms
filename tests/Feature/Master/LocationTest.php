<?php

namespace Tests\Feature\Master;

use App\Enums\LocationType;
use App\Models\Master\Account;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use App\Models\Master\Location;
use App\Models\Master\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
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

    public function test_guest_cannot_view_location_index(): void
    {
        $response = $this->get(route('master.location.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_location(): void
    {
        $location = Location::factory()->create();

        $this->post(route('master.location.store'), ['name' => 'Kệ A1', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->put(route('master.location.update', $location), ['code' => $location->code, 'name' => 'X', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->delete(route('master.location.destroy', $location))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_location_index(): void
    {
        $this->actingAsAdmin();
        Location::factory()->count(3)->create();

        $response = $this->get(route('master.location.index'));

        $response->assertOk();
        $response->assertViewIs('master.location.index');
    }

    public function test_admin_can_create_location_with_auto_generated_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.location.store'), [
            'name'   => 'Kệ A1',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.location.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('locations', ['name' => 'Kệ A1', 'code' => 'VT0001']);
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.location.store'), ['status' => 1]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('locations', 0);
    }

    public function test_store_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.location.store'), [
            'code'   => 'VT-01',
            'name'   => 'Kệ A1',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_code_already_exists(): void
    {
        $this->actingAsAdmin();
        Location::factory()->create(['code' => 'VT0001']);

        $response = $this->post(route('master.location.store'), [
            'code'   => 'VT0001',
            'name'   => 'Kệ A1',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_redirects_with_error_when_virtual_location_has_parent(): void
    {
        $this->actingAsAdmin();
        $parent = Location::factory()->create();

        $response = $this->post(route('master.location.store'), [
            'name'      => 'Vị trí ảo',
            'status'    => 1,
            'type'      => LocationType::Virtual->value,
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect(route('master.location.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('locations', ['name' => 'Vị trí ảo']);
    }

    public function test_admin_can_update_location(): void
    {
        $this->actingAsAdmin();
        $location = Location::factory()->create(['name' => 'Vị trí cũ']);

        $response = $this->put(route('master.location.update', $location), [
            'code'   => $location->code,
            'name'   => 'Vị trí mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.location.index'));
        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'Vị trí mới']);
    }

    public function test_update_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();
        $location = Location::factory()->create();

        $response = $this->put(route('master.location.update', $location), [
            'code'   => 'VT-02',
            'name'   => 'Vị trí mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_update_fails_when_code_already_used_by_another_location(): void
    {
        $this->actingAsAdmin();
        Location::factory()->create(['code' => 'VT0001']);
        $location = Location::factory()->create(['code' => 'VT0002']);

        $response = $this->put(route('master.location.update', $location), [
            'code'   => 'VT0001',
            'name'   => 'Vị trí mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_update_redirects_with_error_when_selecting_a_child_as_parent(): void
    {
        $this->actingAsAdmin();
        $parent = Location::factory()->create();
        $child  = Location::factory()->withParent($parent)->create();

        $response = $this->put(route('master.location.update', $parent), [
            'code'      => $parent->code,
            'name'      => $parent->name,
            'status'    => 1,
            'parent_id' => $child->id,
        ]);

        $response->assertRedirect(route('master.location.index'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_delete_unused_location(): void
    {
        $this->actingAsAdmin();
        $location = Location::factory()->create();

        $response = $this->delete(route('master.location.destroy', $location));

        $response->assertRedirect(route('master.location.index'));
        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    public function test_delete_fails_when_location_has_children(): void
    {
        $this->actingAsAdmin();
        $parent = Location::factory()->create();
        Location::factory()->withParent($parent)->create();

        $response = $this->delete(route('master.location.destroy', $parent));

        $response->assertRedirect(route('master.location.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('locations', ['id' => $parent->id, 'deleted_at' => null]);
    }

    public function test_delete_fails_when_location_is_root_of_a_warehouse(): void
    {
        $this->actingAsAdmin();
        $location = Location::factory()->create();
        Warehouse::create([
            'code'             => 'KHO001',
            'name'             => 'Kho tổng',
            'status'           => 1,
            'root_location_id' => $location->id,
        ]);

        $response = $this->delete(route('master.location.destroy', $location));

        $response->assertRedirect(route('master.location.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('locations', ['id' => $location->id, 'deleted_at' => null]);
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng ban "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_location(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.location.store'), [
            'name'   => 'Kệ A1',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.location.index'));
        $this->assertDatabaseHas('locations', ['name' => 'Kệ A1']);
    }

    public function test_quan_ly_in_kho_department_can_update_location(): void
    {
        $this->actingAsQuanLy('Kho');
        $location = Location::factory()->create(['name' => 'Vị trí cũ']);

        $response = $this->put(route('master.location.update', $location), [
            'code'   => $location->code,
            'name'   => 'Vị trí mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.location.index'));
        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => 'Vị trí mới']);
    }

    public function test_quan_ly_in_kho_department_can_delete_location(): void
    {
        $this->actingAsQuanLy('Kho');
        $location = Location::factory()->create();

        $response = $this->delete(route('master.location.destroy', $location));

        $response->assertRedirect(route('master.location.index'));
        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng ban "Kho" / không có phòng ban (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_location(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.location.store'), [
            'name'   => 'Kệ A1',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_update_location(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $location = Location::factory()->create();

        $response = $this->put(route('master.location.update', $location), [
            'code'   => $location->code,
            'name'   => 'Vị trí mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_delete_location(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $location = Location::factory()->create();

        $response = $this->delete(route('master.location.destroy', $location));

        $response->assertForbidden();
    }

    public function test_quan_ly_without_department_cannot_create_location(): void
    {
        $this->actingAsQuanLy(null);

        $response = $this->post(route('master.location.store'), [
            'name'   => 'Kệ A1',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_location(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.location.store'), [
            'name'   => 'Kệ A1',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_location(): void
    {
        $this->actingAsNhanVien();
        $location = Location::factory()->create();

        $response = $this->put(route('master.location.update', $location), [
            'code'   => $location->code,
            'name'   => 'Vị trí mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_location(): void
    {
        $this->actingAsNhanVien();
        $location = Location::factory()->create();

        $response = $this->delete(route('master.location.destroy', $location));

        $response->assertForbidden();
    }
}
