<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use App\Models\Master\Product;
use App\Models\Master\ReorderRule;
use App\Models\Master\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderRuleTest extends TestCase
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

    private function rulePayload(?Product $product = null, ?Warehouse $warehouse = null, ?Employee $employee = null): array
    {
        return [
            'product_id'   => ($product ?? Product::factory()->create())->id,
            'warehouse_id' => ($warehouse ?? Warehouse::factory()->create())->id,
            'employee_id'  => ($employee ?? Employee::factory()->create())->id,
            'min_qty'      => 10,
            'max_qty'      => 50,
            'status'       => 1,
        ];
    }

    // =========================================================
    // ===== GUEST =====
    // =========================================================

    public function test_guest_cannot_view_reorder_rule_index(): void
    {
        $response = $this->get(route('master.reorder-rule.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_update_or_destroy_reorder_rule(): void
    {
        $rule = ReorderRule::factory()->create();

        $this->post(route('master.reorder-rule.store'), $this->rulePayload())
            ->assertRedirect(route('login'));

        $this->put(route('master.reorder-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'employee_id'  => $rule->employee_id,
            'min_qty'      => 1,
            'max_qty'      => 2,
            'status'       => 1,
        ])->assertRedirect(route('login'));

        $this->delete(route('master.reorder-rule.destroy', $rule))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_reorder_rule_index(): void
    {
        $this->actingAsAdmin();
        ReorderRule::factory()->count(3)->create();

        $response = $this->get(route('master.reorder-rule.index'));

        $response->assertOk();
        $response->assertViewIs('master.reorder-rule.index');
    }

    public function test_admin_can_create_reorder_rule(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        $response = $this->post(route('master.reorder-rule.store'), $this->rulePayload($product));

        $response->assertRedirect(route('master.reorder-rule.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reorder_rules', [
            'product_id' => $product->id,
            'min_qty'    => 10,
            'max_qty'    => 50,
        ]);
    }

    public function test_store_fails_validation_without_product_id(): void
    {
        $this->actingAsAdmin();
        $payload = $this->rulePayload();
        unset($payload['product_id']);

        $response = $this->post(route('master.reorder-rule.store'), $payload);

        $response->assertSessionHasErrors('product_id');
    }

    public function test_store_fails_when_employee_id_missing(): void
    {
        $this->actingAsAdmin();
        $payload = $this->rulePayload();
        unset($payload['employee_id']);

        $response = $this->post(route('master.reorder-rule.store'), $payload);

        $response->assertSessionHasErrors('employee_id');
    }

    public function test_store_fails_when_max_qty_less_than_min_qty(): void
    {
        $this->actingAsAdmin();
        $payload = $this->rulePayload();
        $payload['min_qty'] = 50;
        $payload['max_qty'] = 10;

        $response = $this->post(route('master.reorder-rule.store'), $payload);

        $response->assertSessionHasErrors('max_qty');
    }

    public function test_store_fails_when_min_qty_is_negative(): void
    {
        $this->actingAsAdmin();
        $payload = $this->rulePayload();
        $payload['min_qty'] = -1;

        $response = $this->post(route('master.reorder-rule.store'), $payload);

        $response->assertSessionHasErrors('min_qty');
    }

    public function test_store_fails_when_product_already_has_rule_in_same_warehouse(): void
    {
        $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        ReorderRule::factory()->create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id]);

        $response = $this->post(route('master.reorder-rule.store'), $this->rulePayload($product, $warehouse));

        $response->assertSessionHasErrors('product_id');
    }

    public function test_store_succeeds_when_same_product_used_in_different_warehouse(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();
        ReorderRule::factory()->create(['product_id' => $product->id]); // ở kho khác

        $response = $this->post(route('master.reorder-rule.store'), $this->rulePayload($product));

        $response->assertRedirect(route('master.reorder-rule.index'));
        $this->assertDatabaseCount('reorder_rules', 2);
    }

    /**
     * NOTE: cùng cơ chế với PutawayRule. Rule unique loại trừ bản ghi đã soft-delete
     * (whereNull('deleted_at')) nên gửi lại đúng product+warehouse của rule đã xóa
     * vẫn qua validate — và ReorderRuleService::create() sẽ RESTORE bản ghi cũ
     * (cập nhật employee_id/min_qty/max_qty/note/status) thay vì tạo bản ghi mới.
     */
    public function test_store_restores_trashed_rule_when_same_target_previously_deleted(): void
    {
        $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        $oldRule   = ReorderRule::factory()->create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id]);
        $oldRule->delete(); // soft delete

        $newEmployee = Employee::factory()->create();

        $payload = array_merge(
            $this->rulePayload($product, $warehouse, $newEmployee),
            ['min_qty' => 20, 'max_qty' => 80],
        );

        $response = $this->post(route('master.reorder-rule.store'), $payload);

        $response->assertRedirect(route('master.reorder-rule.index'));
        $this->assertDatabaseCount('reorder_rules', 1); // không tạo thêm bản ghi mới
        $this->assertDatabaseHas('reorder_rules', [
            'id'          => $oldRule->id,
            'employee_id' => $newEmployee->id,
            'min_qty'     => 20,
            'max_qty'     => 80,
            'deleted_at'  => null, // đã được restore
        ]);
    }

    public function test_admin_can_update_reorder_rule(): void
    {
        $this->actingAsAdmin();
        $rule = ReorderRule::factory()->create(['min_qty' => 5, 'max_qty' => 20]);

        $response = $this->put(route('master.reorder-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'employee_id'  => $rule->employee_id,
            'min_qty'      => 15,
            'max_qty'      => 60,
            'status'       => 1,
        ]);

        $response->assertRedirect(route('master.reorder-rule.index'));
        $this->assertDatabaseHas('reorder_rules', ['id' => $rule->id, 'min_qty' => 15, 'max_qty' => 60]);
    }

    public function test_update_fails_when_max_qty_less_than_min_qty(): void
    {
        $this->actingAsAdmin();
        $rule = ReorderRule::factory()->create();

        $response = $this->put(route('master.reorder-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'employee_id'  => $rule->employee_id,
            'min_qty'      => 50,
            'max_qty'      => 10,
            'status'       => 1,
        ]);

        $response->assertSessionHasErrors('max_qty');
    }

    public function test_update_fails_when_product_already_used_by_another_rule_in_same_warehouse(): void
    {
        $this->actingAsAdmin();
        $warehouse = Warehouse::factory()->create();
        $product   = Product::factory()->create();
        ReorderRule::factory()->create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id]);
        $rule = ReorderRule::factory()->create(['warehouse_id' => $warehouse->id]);

        $response = $this->put(route('master.reorder-rule.update', $rule), [
            'product_id'   => $product->id,
            'warehouse_id' => $warehouse->id,
            'employee_id'  => $rule->employee_id,
            'min_qty'      => $rule->min_qty,
            'max_qty'      => $rule->max_qty,
            'status'       => 1,
        ]);

        $response->assertSessionHasErrors('product_id');
    }

    public function test_admin_can_delete_reorder_rule(): void
    {
        $this->actingAsAdmin();
        $rule = ReorderRule::factory()->create();

        $response = $this->delete(route('master.reorder-rule.destroy', $rule));

        $response->assertRedirect(route('master.reorder-rule.index'));
        $this->assertSoftDeleted('reorder_rules', ['id' => $rule->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng ban "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_reorder_rule(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.reorder-rule.store'), $this->rulePayload());

        $response->assertRedirect(route('master.reorder-rule.index'));
        $this->assertDatabaseCount('reorder_rules', 1);
    }

    public function test_quan_ly_in_kho_department_can_update_reorder_rule(): void
    {
        $this->actingAsQuanLy('Kho');
        $rule = ReorderRule::factory()->create(['min_qty' => 5, 'max_qty' => 20]);

        $response = $this->put(route('master.reorder-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'employee_id'  => $rule->employee_id,
            'min_qty'      => 15,
            'max_qty'      => 60,
            'status'       => 1,
        ]);

        $response->assertRedirect(route('master.reorder-rule.index'));
        $this->assertDatabaseHas('reorder_rules', ['id' => $rule->id, 'max_qty' => 60]);
    }

    public function test_quan_ly_in_kho_department_can_delete_reorder_rule(): void
    {
        $this->actingAsQuanLy('Kho');
        $rule = ReorderRule::factory()->create();

        $response = $this->delete(route('master.reorder-rule.destroy', $rule));

        $response->assertRedirect(route('master.reorder-rule.index'));
        $this->assertSoftDeleted('reorder_rules', ['id' => $rule->id]);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng ban "Kho" / không có phòng ban (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_reorder_rule(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.reorder-rule.store'), $this->rulePayload());

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_update_reorder_rule(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $rule = ReorderRule::factory()->create();

        $response = $this->put(route('master.reorder-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'employee_id'  => $rule->employee_id,
            'min_qty'      => $rule->min_qty,
            'max_qty'      => $rule->max_qty,
            'status'       => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_quan_ly_outside_kho_department_cannot_delete_reorder_rule(): void
    {
        $this->actingAsQuanLy('Kế toán');
        $rule = ReorderRule::factory()->create();

        $response = $this->delete(route('master.reorder-rule.destroy', $rule));

        $response->assertForbidden();
    }

    public function test_quan_ly_without_department_cannot_create_reorder_rule(): void
    {
        $this->actingAsQuanLy(null);

        $response = $this->post(route('master.reorder-rule.store'), $this->rulePayload());

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN (luôn bị từ chối) =====
    // =========================================================

    public function test_nhan_vien_cannot_create_reorder_rule(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.reorder-rule.store'), $this->rulePayload());

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_reorder_rule(): void
    {
        $this->actingAsNhanVien();
        $rule = ReorderRule::factory()->create();

        $response = $this->put(route('master.reorder-rule.update', $rule), [
            'product_id'   => $rule->product_id,
            'warehouse_id' => $rule->warehouse_id,
            'employee_id'  => $rule->employee_id,
            'min_qty'      => $rule->min_qty,
            'max_qty'      => $rule->max_qty,
            'status'       => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_reorder_rule(): void
    {
        $this->actingAsNhanVien();
        $rule = ReorderRule::factory()->create();

        $response = $this->delete(route('master.reorder-rule.destroy', $rule));

        $response->assertForbidden();
    }
}
