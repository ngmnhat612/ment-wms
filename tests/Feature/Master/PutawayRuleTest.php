<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Category;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Master\PutawayRule;
use App\Models\Master\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutawayRuleTest extends TestCase
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

    private function productRulePayload(?Warehouse $warehouse = null, ?Product $product = null, ?Location $location = null): array
    {
        return [
            'apply_on'     => 'product',
            'product_id'   => ($product ?? Product::factory()->create())->id,
            'warehouse_id' => ($warehouse ?? Warehouse::factory()->create())->id,
            'location_id'  => ($location ?? Location::factory()->create())->id,
            'status'       => 1,
        ];
    }

    private function categoryRulePayload(?Warehouse $warehouse = null, ?Category $category = null, ?Location $location = null): array
    {
        return [
            'apply_on'     => 'category',
            'category_id'  => ($category ?? Category::factory()->create())->id,
            'warehouse_id' => ($warehouse ?? Warehouse::factory()->create())->id,
            'location_id'  => ($location ?? Location::factory()->create())->id,
            'status'       => 1,
        ];
    }

    // =========================================================
    // ===== GUEST =====
    // =========================================================

    public function test_guest_cannot_view_putaway_rule_index(): void
    {
        $response = $this->get(route('master.putaway-rule.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_putaway_rule(): void
    {
        $rule = PutawayRule::factory()->create();

        $this->post(route('master.putaway-rule.store'), $this->productRulePayload())
            ->assertRedirect(route('login'));

        $this->put(route('master.putaway-rule.update', $rule), ['location_id' => $rule->location_id, 'warehouse_id' => $rule->warehouse_id, 'status' => 1])
            ->assertRedirect(route('login'));

        $this->delete(route('master.putaway-rule.destroy', $rule))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_putaway_rule_index(): void
    {
        $this->actingAsAdmin();
        PutawayRule::factory()->count(3)->create();

        $response = $this->get(route('master.putaway-rule.index'));

        $response->assertOk();
        $response->assertViewIs('master.putaway-rule.index');
    }

    public function test_admin_can_create_product_rule(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $response = $this->post(route('master.putaway-rule.store'), $this->productRulePayload(product: $product));

        $response->assertRedirect(route('master.putaway-rule.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('putaway_rules', [
            'product_id'  => $product->id,
            'category_id' => null,
        ]);
    }

    public function test_admin_can_create_category_rule(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create();

        $response = $this->post(route('master.putaway-rule.store'), $this->categoryRulePayload(category: $category));

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertDatabaseHas('putaway_rules', [
            'category_id' => $category->id,
            'product_id'  => null,
        ]);
    }

    public function test_store_fails_validation_without_apply_on(): void
    {
        $this->actingAsAdmin();
        $payload = $this->productRulePayload();
        unset($payload['apply_on']);

        $response = $this->post(route('master.putaway-rule.store'), $payload);

        $response->assertSessionHasErrors('apply_on');
    }

    public function test_store_fails_when_apply_on_product_but_product_id_missing(): void
    {
        $this->actingAsAdmin();
        $payload = $this->productRulePayload();
        unset($payload['product_id']);

        $response = $this->post(route('master.putaway-rule.store'), $payload);

        $response->assertSessionHasErrors('product_id');
    }

    public function test_store_fails_when_apply_on_category_but_category_id_missing(): void
    {
        $this->actingAsAdmin();
        $payload = $this->categoryRulePayload();
        unset($payload['category_id']);

        $response = $this->post(route('master.putaway-rule.store'), $payload);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_store_fails_when_location_id_missing(): void
    {
        $this->actingAsAdmin();
        $payload = $this->productRulePayload();
        unset($payload['location_id']);

        $response = $this->post(route('master.putaway-rule.store'), $payload);

        $response->assertSessionHasErrors('location_id');
    }

    public function test_store_fails_when_warehouse_id_does_not_exist(): void
    {
        $this->actingAsAdmin();
        $payload = $this->productRulePayload();
        $payload['warehouse_id'] = 999999;

        $response = $this->post(route('master.putaway-rule.store'), $payload);

        $response->assertSessionHasErrors('warehouse_id');
    }

    public function test_store_fails_when_product_already_has_rule_in_same_warehouse(): void
    {
        $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        PutawayRule::factory()->create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id]);

        $response = $this->post(route('master.putaway-rule.store'), $this->productRulePayload($warehouse, $product));

        $response->assertSessionHasErrors('product_id');
    }

    public function test_store_fails_when_category_already_has_rule_in_same_warehouse(): void
    {
        $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $category  = Category::factory()->create();
        PutawayRule::factory()->forCategory()->create(['warehouse_id' => $warehouse->id, 'category_id' => $category->id]);

        $response = $this->post(route('master.putaway-rule.store'), $this->categoryRulePayload($warehouse, $category));

        $response->assertSessionHasErrors('category_id');
    }

    public function test_store_succeeds_when_same_product_used_in_different_warehouse(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();
        PutawayRule::factory()->create(['product_id' => $product->id]); // ở kho khác

        $response = $this->post(route('master.putaway-rule.store'), $this->productRulePayload(product: $product));

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertDatabaseCount('putaway_rules', 2);
    }

    /**
     * NOTE: hành vi đặc trưng của PutawayRule. Rule unique validate loại trừ bản ghi
     * đã soft-delete (whereNull('deleted_at')) nên việc gửi lại đúng warehouse+product
     * của một rule đã xóa mềm vẫn qua được validate — và PutawayRuleService::create()
     * sẽ RESTORE bản ghi cũ (cập nhật location/note/status) thay vì tạo bản ghi mới.
     */
    public function test_store_restores_trashed_rule_when_same_target_previously_deleted(): void
    {
        $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        $oldRule   = PutawayRule::factory()->create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id]);
        $oldRule->delete(); // soft delete

        $newLocation = Location::factory()->create();

        $response = $this->post(route('master.putaway-rule.store'), $this->productRulePayload($warehouse, $product, $newLocation));

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertDatabaseCount('putaway_rules', 1); // không tạo thêm bản ghi mới
        $this->assertDatabaseHas('putaway_rules', [
            'id'          => $oldRule->id,
            'location_id' => $newLocation->id,
            'deleted_at'  => null, // đã được restore
        ]);
    }

    public function test_admin_can_update_putaway_rule(): void
    {
        $this->actingAsAdmin();
        $rule        = PutawayRule::factory()->create();
        $newLocation = Location::factory()->create();

        $response = $this->put(route('master.putaway-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'location_id'  => $newLocation->id,
            'status'       => 1,
        ]);

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertDatabaseHas('putaway_rules', ['id' => $rule->id, 'location_id' => $newLocation->id]);
    }

    /**
     * NOTE: UpdatePutawayRuleRequest tự suy ra apply_on từ bản ghi hiện có
     * ($rule->product_id ? 'product' : 'category') trong prepareForValidation(),
     * bỏ qua apply_on/category_id người dùng cố gửi lên -> KHÔNG thể đổi loại rule qua update.
     */
    public function test_update_ignores_attempt_to_change_rule_type_from_product_to_category(): void
    {
        $this->actingAsAdmin();
        $rule     = PutawayRule::factory()->create(); // rule theo product
        $category = Category::factory()->create();

        $response = $this->put(route('master.putaway-rule.update', $rule), [
            'apply_on'     => 'category', // cố tình gửi sai, sẽ bị ghi đè
            'category_id'  => $category->id, // cố tình gửi kèm, sẽ bị merge về null
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'location_id'  => $rule->location_id,
            'status'       => 1,
        ]);

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertDatabaseHas('putaway_rules', [
            'id'          => $rule->id,
            'product_id'  => $rule->product_id,
            'category_id' => null,
        ]);
    }

    public function test_update_fails_when_location_id_missing(): void
    {
        $this->actingAsAdmin();
        $rule = PutawayRule::factory()->create();

        $response = $this->put(route('master.putaway-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'status'       => 1,
        ]);

        $response->assertSessionHasErrors('location_id');
    }

    public function test_update_fails_when_product_already_used_by_another_rule_in_same_warehouse(): void
    {
        $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        PutawayRule::factory()->create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id]);
        $rule = PutawayRule::factory()->create(['warehouse_id' => $warehouse->id]);

        $response = $this->put(route('master.putaway-rule.update', $rule), [
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'location_id'  => $rule->location_id,
            'status'       => 1,
        ]);

        $response->assertSessionHasErrors('product_id');
    }

    public function test_admin_can_delete_putaway_rule(): void
    {
        $this->actingAsAdmin();
        $rule = PutawayRule::factory()->create();

        $response = $this->delete(route('master.putaway-rule.destroy', $rule));

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertSoftDeleted('putaway_rules', ['id' => $rule->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng ban "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_putaway_rule(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.putaway-rule.store'), $this->productRulePayload());

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertDatabaseCount('putaway_rules', 1);
    }

    public function test_quan_ly_in_kho_department_can_update_putaway_rule(): void
    {
        $this->actingAsQuanLy('Kho');
        $rule        = PutawayRule::factory()->create();
        $newLocation = Location::factory()->create();

        $response = $this->put(route('master.putaway-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'location_id'  => $newLocation->id,
            'status'       => 1,
        ]);

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertDatabaseHas('putaway_rules', ['id' => $rule->id, 'location_id' => $newLocation->id]);
    }

    public function test_quan_ly_in_kho_department_can_delete_putaway_rule(): void
    {
        $this->actingAsQuanLy('Kho');
        $rule = PutawayRule::factory()->create();

        $response = $this->delete(route('master.putaway-rule.destroy', $rule));

        $response->assertRedirect(route('master.putaway-rule.index'));
        $this->assertSoftDeleted('putaway_rules', ['id' => $rule->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng ban "Kho" / không có phòng ban (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_putaway_rule(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.putaway-rule.store'), $this->productRulePayload());

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_update_putaway_rule(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $rule = PutawayRule::factory()->create();

        $response = $this->put(route('master.putaway-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'location_id'  => $rule->location_id,
            'status'       => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_delete_putaway_rule(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $rule = PutawayRule::factory()->create();

        $response = $this->delete(route('master.putaway-rule.destroy', $rule));

        $response->assertForbidden();
    }

    public function test_quan_ly_without_department_cannot_create_putaway_rule(): void
    {
        $this->actingAsQuanLy(null);

        $response = $this->post(route('master.putaway-rule.store'), $this->productRulePayload());

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_putaway_rule(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.putaway-rule.store'), $this->productRulePayload());

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_putaway_rule(): void
    {
        $this->actingAsNhanVien();
        $rule = PutawayRule::factory()->create();

        $response = $this->put(route('master.putaway-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'location_id'  => $rule->location_id,
            'status'       => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_putaway_rule(): void
    {
        $this->actingAsNhanVien();
        $rule = PutawayRule::factory()->create();

        $response = $this->delete(route('master.putaway-rule.destroy', $rule));

        $response->assertForbidden();
    }
}
