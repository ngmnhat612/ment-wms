<?php

namespace Tests\Unit\Services;

use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Master\PutawayRule;
use App\Models\Master\Warehouse;
use App\Repositories\Contracts\Master\PutawayRuleRepositoryInterface;
use App\Services\Master\PutawayRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

/**
 * Dùng RefreshDatabase (thay vì PHPUnit\Framework\TestCase thuần như Sn/Supplier/DepartmentServiceTest)
 * vì deleteForProduct() gọi thẳng PutawayRule::where(...)->first() trên Eloquent,
 * không đi qua PutawayRuleRepositoryInterface nên không thể mock hoàn toàn.
 * Đây là cùng pattern đã dùng trong ProductServiceTest.
 */
class PutawayRuleServiceTest extends TestCase
{
    use RefreshDatabase, MockeryPHPUnitIntegration;

    private PutawayRuleRepositoryInterface $repo;
    private PutawayRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo    = Mockery::mock(PutawayRuleRepositoryInterface::class);
        $this->service = new PutawayRuleService($this->repo);
    }

    // ===== CREATE =====

    public function test_create_calls_repository_create_when_no_trashed_rule_exists(): void
    {
        $data = [
            'warehouse_id' => 1,
            'product_id'   => 2,
            'category_id'  => null,
            'location_id'  => 3,
            'note'         => null,
            'status'       => 1,
        ];

        $this->repo->shouldReceive('findTrashed')->once()->with(1, 2, null)->andReturn(null);
        $this->repo->shouldNotReceive('restoreAndUpdate');
        $this->repo->shouldReceive('create')->once()->with($data)->andReturn(new PutawayRule($data));

        $result = $this->service->create($data);

        $this->assertSame(3, $result->location_id);
    }

    public function test_create_for_category_rule_looks_up_trashed_by_category(): void
    {
        $data = [
            'warehouse_id' => 5,
            'product_id'   => null,
            'category_id'  => 7,
            'location_id'  => 8,
            'status'       => 1,
        ];

        $this->repo->shouldReceive('findTrashed')->once()->with(5, null, 7)->andReturn(null);
        $this->repo->shouldReceive('create')->once()->andReturn(new PutawayRule($data));

        $this->service->create($data);
    }

    /**
     * NOTE: hành vi đặc trưng riêng của PutawayRule, không giống bất kỳ service nào trước đó
     * (Uom/Sn/Supplier/Department chỉ create() thẳng, không có bước kiểm tra bản ghi đã xóa mềm).
     * Nếu cặp warehouse+product (hoặc warehouse+category) từng có rule bị soft-delete,
     * create() sẽ RESTORE bản ghi cũ và cập nhật location/note/status, KHÔNG tạo bản ghi mới.
     */
    public function test_create_restores_trashed_rule_instead_of_creating_new_one(): void
    {
        $trashed = Mockery::mock(PutawayRule::class)->makePartial();

        $data = [
            'warehouse_id' => 1,
            'product_id'   => 2,
            'category_id'  => null,
            'location_id'  => 99,
            'note'         => 'Ghi chú mới',
            'status'       => 1,
        ];

        $this->repo->shouldReceive('findTrashed')->once()->with(1, 2, null)->andReturn($trashed);
        $this->repo->shouldReceive('restoreAndUpdate')
            ->once()
            ->with($trashed, [
                'location_id' => 99,
                'note'        => 'Ghi chú mới',
                'status'      => 1,
            ])
            ->andReturn($trashed);
        $this->repo->shouldNotReceive('create');

        $result = $this->service->create($data);

        $this->assertSame($trashed, $result);
    }

    // ===== UPDATE =====

    public function test_update_passes_data_straight_to_repository(): void
    {
        $rule = Mockery::mock(PutawayRule::class)->makePartial();
        $rule->shouldReceive('fresh')->once()->andReturn($rule);

        $data = ['location_id' => 10, 'note' => 'x', 'status' => 0];

        $this->repo->shouldReceive('update')->once()->with($rule, $data)->andReturn(true);

        $result = $this->service->update($rule, $data);

        $this->assertSame($rule, $result);
    }

    // ===== DELETE =====

    /**
     * NOTE: khác Uom/Supplier (kiểm tra ràng buộc trước khi xóa), delete() ở đây
     * gọi thẳng repository, không kiểm tra gì thêm — giống pattern của Sn.
     */
    public function test_delete_calls_repository_delete_directly_without_dependency_check(): void
    {
        $rule = new PutawayRule(['location_id' => 1]);

        $this->repo->shouldReceive('delete')->once()->with($rule)->andReturn(true);

        $this->service->delete($rule);

        $this->assertTrue(true); // không throw
    }

    // ===== SYNC FOR PRODUCT (được gọi từ ProductService khi thêm/sửa vật tư) =====

    public function test_sync_for_product_updates_existing_rule_location_only(): void
    {
        $existing = Mockery::mock(PutawayRule::class)->makePartial();
        $existing->shouldReceive('fresh')->once()->andReturn($existing);

        $this->repo->shouldReceive('findByProductAndWarehouse')->once()->with(2, 1)->andReturn($existing);
        $this->repo->shouldReceive('update')->once()->with($existing, ['location_id' => 5])->andReturn(true);
        $this->repo->shouldNotReceive('findTrashed');
        $this->repo->shouldNotReceive('create');

        $this->service->syncForProduct(2, 1, 5);
    }

    public function test_sync_for_product_creates_new_product_rule_when_none_exists(): void
    {
        $this->repo->shouldReceive('findByProductAndWarehouse')->once()->with(2, 1)->andReturn(null);
        $this->repo->shouldReceive('findTrashed')->once()->with(1, 2, null)->andReturn(null);
        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['warehouse_id'] === 1
                && $data['product_id'] === 2
                && $data['category_id'] === null
                && $data['location_id'] === 5
            ))
            ->andReturn(new PutawayRule(['product_id' => 2]));

        $this->service->syncForProduct(2, 1, 5);
    }

    // ===== DELETE FOR PRODUCT (được gọi từ ProductService khi xóa vật tư) =====

    /**
     * NOTE: deleteForProduct() dùng trực tiếp PutawayRule::where('product_id', ...)->first(),
     * không đi qua repository -> cần DB thật (RefreshDatabase) để kiểm chứng, không mock được
     * hoàn toàn như các nhánh trên.
     */
    public function test_delete_for_product_deletes_rule_when_found(): void
    {
        $warehouse = Warehouse::factory()->create();
        $location  = Location::factory()->create();
        $product   = Product::factory()->create();

        $rule = PutawayRule::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'category_id'  => null,
            'location_id'  => $location->id,
            'status'       => 1,
        ]);

        $this->repo->shouldReceive('delete')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $rule->id))
            ->andReturn(true);

        $this->service->deleteForProduct($product->id);
    }

    public function test_delete_for_product_does_nothing_when_no_rule_found(): void
    {
        $this->repo->shouldNotReceive('delete');

        $this->service->deleteForProduct(999999);

        $this->assertTrue(true); // không throw, không gọi delete
    }
}
