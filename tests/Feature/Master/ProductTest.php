<?php

namespace Tests\Feature\Master;

use App\Models\Master\Account;
use App\Models\Master\Category;
use App\Models\Master\Department;
use App\Models\Master\Employee;
use App\Models\Master\Product;
use App\Models\Master\Uom;
use App\Models\Master\Warehouse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Warehouse::factory()->create();
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'           => 'Ốc vít M8',
            'category_id'    => $overrides['category_id'] ?? Category::factory()->create()->id,
            'uom_id'         => $overrides['uom_id'] ?? Uom::factory()->create()->id,
            'tracking_type'  => 1,
            'stock_rotation' => 1,
            'status'         => 1,
        ], $overrides);
    }

    // =========================================================
    // ===== GUEST =====
    // =========================================================

    public function test_guest_cannot_store_update_or_destroy_product(): void
    {
        $product = Product::factory()->create();

        $this->post(route('master.product.store'), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->put(route('master.product.update', $product), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->delete(route('master.product.destroy', $product))
            ->assertRedirect(route('login'));
    }

    // =========================================================
    // ===== ADMIN =====
    // =========================================================

    public function test_admin_can_view_product_index(): void
    {
        $this->actingAsAdmin();
        Product::factory()->count(3)->create();

        $response = $this->get(route('master.product.index'));

        $response->assertOk();
        $response->assertViewIs('master.product.index');
    }

    public function test_admin_can_create_product_with_auto_generated_code(): void
    {
        $this->actingAsAdmin();
        $category = Category::factory()->create(['code' => 'DMOC01']);
        $uom      = Uom::factory()->create();

        $response = $this->post(route('master.product.store'), $this->validPayload([
            'category_id' => $category->id,
            'uom_id'      => $uom->id,
        ]));

        $response->assertRedirect(route('master.product.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('products', ['name' => 'Ốc vít M8', 'code' => 'DM0001']);
    }

    public function test_store_fails_validation_without_category_and_uom(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.product.store'), [
            'name'           => 'Ốc vít M8',
            'tracking_type'  => 1,
            'stock_rotation' => 1,
            'status'         => 1,
        ]);

        $response->assertSessionHasErrors(['category_id', 'uom_id']);
    }

    public function test_store_fails_when_fefo_requires_alert_before_expiry(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('master.product.store'), $this->validPayload([
            'stock_rotation' => 2, // FEFO
            // thiếu alert_before_expiry
        ]));

        $response->assertSessionHasErrors('alert_before_expiry');
    }

    public function test_store_fails_when_code_already_exists(): void
    {
        $this->actingAsAdmin();
        $existing = Product::factory()->create(['code' => 'DM0001']);

        $response = $this->post(route('master.product.store'), $this->validPayload([
            'code' => 'DM0001',
        ]));

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_can_update_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['name' => 'Tên cũ']);

        $response = $this->put(route('master.product.update', $product), $this->validPayload([
            'name' => 'Tên mới',
        ]));

        $response->assertRedirect(route('master.product.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Tên mới']);
    }

    public function test_update_ignores_attempt_to_change_code(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create(['code' => 'DM0001']);

        $this->put(route('master.product.update', $product), $this->validPayload([
            'code' => 'HACKED01',
        ]));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'code' => 'DM0001']);
    }

    public function test_admin_can_delete_product(): void
    {
        $this->actingAsAdmin();
        $product = Product::factory()->create();

        // Lưu ý: ProductRepository::hasStock() hiện luôn trả về false (xem ghi chú đầu bài),
        // nên xóa luôn thành công bất kể tồn kho thật — test này phản ánh đúng hành vi hiện tại.
        $response = $this->delete(route('master.product.destroy', $product));

        $response->assertRedirect(route('master.product.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_create_variant_inheriting_parent_and_deactivates_family(): void
    {
        $this->actingAsAdmin();
        $parent = Product::factory()->create([
            'code'           => 'DD1234',
            'tracking_type'  => 2,
            'stock_rotation' => 1,
            'status'         => 1,
        ]);

        $response = $this->post(route('master.product.storeVariant'), [
            'parent_code'    => 'DD1234',
            'name'           => 'Dây điện - Đỏ',
            'tracking_type'  => 1, // sẽ bị ghi đè bởi giá trị kế thừa từ cha
            'stock_rotation' => 1,
            'status'         => 1,
        ]);

        $response->assertRedirect(route('master.product.index'));
        $this->assertDatabaseHas('products', [
            'code'          => 'DD1234.1',
            'parent_id'     => $parent->id,
            'category_id'   => $parent->category_id,
            'uom_id'        => $parent->uom_id,
            'tracking_type' => 2, // kế thừa từ cha, không phải giá trị gửi lên
        ]);
        $this->assertDatabaseHas('products', ['id' => $parent->id, 'status' => 0]); // cha bị ngưng hoạt động
    }

    // =========================================================
    // ===== QUẢN LÝ - thuộc phòng "Kho" (được phép) =====
    // =========================================================

    public function test_quan_ly_in_kho_department_can_create_product(): void
    {
        $this->actingAsQuanLy('Kho');

        $response = $this->post(route('master.product.store'), $this->validPayload());

        $response->assertRedirect(route('master.product.index'));
        $this->assertDatabaseHas('products', ['name' => 'Ốc vít M8']);
    }

    // =========================================================
    // ===== QUẢN LÝ - ngoài phòng "Kho" (bị từ chối) =====
    // =========================================================

    public function test_quan_ly_outside_kho_department_cannot_create_product(): void
    {
        $this->actingAsQuanLy('Kế toán');

        $response = $this->post(route('master.product.store'), $this->validPayload());

        $response->assertForbidden();
    }

    // =========================================================
    // ===== NHÂN VIÊN =====
    // =========================================================

    public function test_nhan_vien_can_view_product_index(): void
    {
        // ProductPolicy::viewAny() trả về true không điều kiện — khác Uom, mọi role đều xem được
        $this->actingAsNhanVien();

        $response = $this->get(route('master.product.index'));

        $response->assertOk();
    }

    public function test_nhan_vien_cannot_create_product(): void
    {
        $this->actingAsNhanVien();

        $response = $this->post(route('master.product.store'), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_update_product(): void
    {
        $this->actingAsNhanVien();
        $product = Product::factory()->create();

        $response = $this->put(route('master.product.update', $product), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_nhan_vien_cannot_delete_product(): void
    {
        $this->actingAsNhanVien();
        $product = Product::factory()->create();

        $response = $this->delete(route('master.product.destroy', $product));

        $response->assertForbidden();
    }
}
