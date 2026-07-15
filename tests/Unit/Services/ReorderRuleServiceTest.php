<?php

namespace Tests\Unit\Services;

use App\Models\Master\ReorderRule;
use App\Models\Master\Warehouse;
use App\Repositories\Contracts\Master\ReorderRuleRepositoryInterface;
use App\Services\Master\ReorderRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

/**
 * Dùng RefreshDatabase (như PutawayRuleServiceTest) vì defaultWarehouseId() gọi thẳng
 * Warehouse::query()->value('id') trên Eloquent, không đi qua repository nên cần DB thật.
 */
class ReorderRuleServiceTest extends TestCase
{
    use RefreshDatabase, MockeryPHPUnitIntegration;

    private ReorderRuleRepositoryInterface $repo;
    private ReorderRuleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo    = Mockery::mock(ReorderRuleRepositoryInterface::class);
        $this->service = new ReorderRuleService($this->repo);
    }

    // ===== CREATE =====

    public function test_create_calls_repository_create_when_no_trashed_rule_exists(): void
    {
        $data = [
            'product_id'   => 2,
            'warehouse_id' => 1,
            'employee_id'  => 4,
            'min_qty'      => 10,
            'max_qty'      => 50,
            'note'         => null,
            'status'       => 1,
        ];

        $this->repo->shouldReceive('findTrashed')->once()->with(2, 1)->andReturn(null);
        $this->repo->shouldNotReceive('restoreAndUpdate');
        $this->repo->shouldReceive('create')->once()->with($data)->andReturn(new ReorderRule($data));

        $result = $this->service->create($data);

        $this->assertEquals(50, $result->max_qty);
    }

    /**
     * NOTE: cùng cơ chế với PutawayRuleService — nếu product+warehouse từng có
     * rule bị soft-delete, create() sẽ RESTORE bản ghi cũ (cập nhật employee_id/
     * min_qty/max_qty/note/status) thay vì tạo bản ghi mới.
     */
    public function test_create_restores_trashed_rule_instead_of_creating_new_one(): void
    {
        $trashed = Mockery::mock(ReorderRule::class)->makePartial();

        $data = [
            'product_id'   => 2,
            'warehouse_id' => 1,
            'employee_id'  => 9,
            'min_qty'      => 5,
            'max_qty'      => 20,
            'note'         => 'Ghi chú',
            'status'       => 1,
        ];

        $this->repo->shouldReceive('findTrashed')->once()->with(2, 1)->andReturn($trashed);
        $this->repo->shouldReceive('restoreAndUpdate')
            ->once()
            ->with($trashed, [
                'employee_id' => 9,
                'min_qty'     => 5,
                'max_qty'     => 20,
                'note'        => 'Ghi chú',
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
        $rule = Mockery::mock(ReorderRule::class)->makePartial();
        $rule->shouldReceive('fresh')->once()->andReturn($rule);

        $data = ['min_qty' => 15, 'max_qty' => 60, 'employee_id' => 3, 'status' => 1];

        $this->repo->shouldReceive('update')->once()->with($rule, $data)->andReturn(true);

        $result = $this->service->update($rule, $data);

        $this->assertSame($rule, $result);
    }

    // ===== DELETE =====

    /**
     * NOTE: khác Uom/Supplier (kiểm tra ràng buộc trước khi xóa), delete() ở đây
     * gọi thẳng repository, không kiểm tra gì thêm — giống pattern của Sn/PutawayRule.
     */
    public function test_delete_calls_repository_delete_directly_without_dependency_check(): void
    {
        $rule = new ReorderRule(['min_qty' => 1]);

        $this->repo->shouldReceive('delete')->once()->with($rule)->andReturn(true);

        $this->service->delete($rule);

        $this->assertTrue(true); // không throw
    }

    // ===== SYNC FOR PRODUCT (được gọi từ ProductService khi thêm/sửa vật tư) =====

    public function test_sync_for_product_updates_existing_rule_min_max_only(): void
    {
        $existing = Mockery::mock(ReorderRule::class)->makePartial();
        $existing->shouldReceive('fresh')->once()->andReturn($existing);

        $this->repo->shouldReceive('findByProductAndWarehouse')->once()->with(2, 1)->andReturn($existing);
        $this->repo->shouldReceive('update')->once()->with($existing, ['min_qty' => 5, 'max_qty' => 20])->andReturn(true);
        $this->repo->shouldNotReceive('findTrashed');
        $this->repo->shouldNotReceive('create');

        $this->service->syncForProduct(2, 1, 5, 20);
    }

    public function test_sync_for_product_creates_new_rule_when_none_exists(): void
    {
        $this->repo->shouldReceive('findByProductAndWarehouse')->once()->with(2, 1)->andReturn(null);
        $this->repo->shouldReceive('findTrashed')->once()->with(2, 1)->andReturn(null);
        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['product_id'] === 2
                && $data['warehouse_id'] === 1
                && $data['employee_id'] === null
                && $data['min_qty'] === 0
                && $data['max_qty'] === 0
            ))
            ->andReturn(new ReorderRule(['product_id' => 2]));

        $this->service->syncForProduct(2, 1, 0, 0);
    }

    // ===== DELETE FOR PRODUCT =====

    /**
     * NOTE: khác PutawayRuleService::deleteForProduct() (gọi thẳng Eloquent),
     * ReorderRuleService::deleteForProduct() đi qua repository->deleteByProduct()
     * nên mock thuần được, không cần DB thật.
     */
    public function test_delete_for_product_delegates_to_repository(): void
    {
        $this->repo->shouldReceive('deleteByProduct')->once()->with(7);

        $this->service->deleteForProduct(7);

        $this->assertTrue(true);
    }

    // ===== DEFAULT WAREHOUSE ID =====

    /**
     * NOTE: defaultWarehouseId() cache kết quả vào `static $id` bên trong method
     * (không phải property của instance) -> giá trị này được CHIA SẺ giữa mọi lần
     * gọi trong cùng 1 process PHP, kể cả từ các instance/test khác. Cả 2 test dưới
     * đây phải chạy @runInSeparateProcess để không bị nhiễu bởi cache của nhau.
     */

    /**
     * @runInSeparateProcess
     */
    public function test_default_warehouse_id_returns_first_warehouse_id(): void
    {
        $warehouse = Warehouse::factory()->create();

        $this->assertSame($warehouse->id, $this->service->defaultWarehouseId());
    }

    /**
     * @runInSeparateProcess
     */
    public function test_default_warehouse_id_throws_when_no_warehouse_exists(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Chưa có kho nào trong hệ thống.');

        $this->service->defaultWarehouseId();
    }
}
