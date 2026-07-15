<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Category;
use App\Models\Master\Product;
use App\Models\Master\Uom;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_guest_cannot_view_category_index(): void
    {
        $response = $this->get(route('master.category.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_category(): void
    {
        $category = Category::factory()->create();

        $this->post(route('master.category.store'), ['name' => 'Nhóm A', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->put(route('master.category.update', $category), ['code' => $category->code, 'name' => 'X', 'status' => 1])
            ->assertRedirect(route('login'));

        $this->delete(route('master.category.destroy', $category))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_category_index(): void
    {
        $this->actingAsAdmin();
        Category::factory()->count(3)->create();

        $response = $this->get(route('master.category.index'));

        $response->assertOk();
        $response->assertViewIs('master.category.index');
    }

    public function test_admin_can_create_category_with_auto_generated_code(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.category.store'), [
            'name'   => 'Nhóm A',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.category.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('categories', ['name' => 'Nhóm A', 'code' => 'DM0001']);
    }

    public function test_store_fails_validation_without_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.category.store'), ['status' => 1]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_store_fails_when_code_contains_special_characters(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.category.store'), [
            'code'   => 'DM-01',
            'name'   => 'Nhóm A',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_code_already_exists(): void
    {
        $this->actingAsAdmin();
        Category::factory()->create(['code' => 'DM0001']);

        $response = $this->post(route('master.category.store'), [
            'code'   => 'DM0001',
            'name'   => 'Nhóm A',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_when_parent_id_does_not_exist(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.category.store'), [
            'name'      => 'Nhóm A',
            'parent_id' => 999999,
            'status'    => 1,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_admin_can_update_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['name' => 'Nhóm cũ']);

        $response = $this->put(route('master.category.update', $category), [
            'code'   => $category->code,
            'name'   => 'Nhóm mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.category.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Nhóm mới']);
    }

    public function test_update_fails_without_code(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->put(route('master.category.update', $category), [
            'name'   => 'Nhóm mới',
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_delete_unused_category(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->delete(route('master.category.destroy', $category));

        $response->assertRedirect(route('master.category.index'));
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_delete_fails_when_category_used_by_a_product(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();
        $uom      = Uom::factory()->create();

        Product::create([
            'code'        => 'SP0001',
            'name'        => 'Vật tư test',
            'category_id' => $category->id,
            'uom_id'      => $uom->id,
            'status'      => 1,
        ]);

        $response = $this->delete(route('master.category.destroy', $category));

        $response->assertRedirect(route('master.category.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng ban "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_category(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.category.store'), [
            'name'   => 'Nhóm A',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.category.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Nhóm A']);
    }

    public function test_quan_ly_in_kho_department_can_update_category(): void
    {
        $this->actingAsQuanLy('Kho');
        $category = Category::factory()->create(['name' => 'Nhóm cũ']);

        $response = $this->put(route('master.category.update', $category), [
            'code'   => $category->code,
            'name'   => 'Nhóm mới',
            'status' => 1,
        ]);

        $response->assertRedirect(route('master.category.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Nhóm mới']);
    }

    public function test_quan_ly_in_kho_department_can_delete_category(): void
    {
        $this->actingAsQuanLy('Kho');
        $category = Category::factory()->create();

        $response = $this->delete(route('master.category.destroy', $category));

        $response->assertRedirect(route('master.category.index'));
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng ban "Kho" / không có phòng ban (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_category(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.category.store'), [
            'name'   => 'Nhóm A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_update_category(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $category = Category::factory()->create();

        $response = $this->put(route('master.category.update', $category), [
            'code'   => $category->code,
            'name'   => 'Nhóm mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_delete_category(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $category = Category::factory()->create();

        $response = $this->delete(route('master.category.destroy', $category));

        $response->assertForbidden();
    }

    public function test_quan_ly_without_department_cannot_create_category(): void
    {
        $this->actingAsQuanLy(null);

        $response = $this->post(route('master.category.store'), [
            'name'   => 'Nhóm A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_category(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.category.store'), [
            'name'   => 'Nhóm A',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_category(): void
    {
        $this->actingAsNhanVien();
        $category = Category::factory()->create();

        $response = $this->put(route('master.category.update', $category), [
            'code'   => $category->code,
            'name'   => 'Nhóm mới',
            'status' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_category(): void
    {
        $this->actingAsNhanVien();
        $category = Category::factory()->create();

        $response = $this->delete(route('master.category.destroy', $category));

        $response->assertForbidden();
    }
}
