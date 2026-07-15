<?php

namespace Tests\Unit\Services;

use App\Models\Master\Category;
use App\Models\Master\Product;
use App\Models\Master\Uom;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Master\ProductService;
use App\Services\Master\PutawayRuleService;
use App\Services\Master\ReorderRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase, MockeryPHPUnitIntegration;

    private ProductRepositoryInterface $productRepo;
    private CodeGeneratorService $codeGen;
    private ReorderRuleService $reorderRuleService;
    private PutawayRuleService $putawayRuleService;
    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepo        = Mockery::mock(ProductRepositoryInterface::class);
        $this->codeGen            = Mockery::mock(CodeGeneratorService::class);
        $this->reorderRuleService = Mockery::mock(ReorderRuleService::class);
        $this->putawayRuleService = Mockery::mock(PutawayRuleService::class);

        $this->service = new ProductService(
            $this->productRepo,
            $this->codeGen,
            $this->reorderRuleService,
            $this->putawayRuleService,
        );
    }

    // ===== CREATE =====

    public function test_create_auto_generates_code_using_category_prefix(): void
    {
        $category = Category::factory()->create(['code' => 'DMDIEN01']);
        $uom      = Uom::factory()->create();

        $this->codeGen->shouldReceive('generateCode')
            ->once()
            ->with('products', 'code', 'DM')
            ->andReturn('DM0001');

        $fakeProduct = new Product(['code' => 'DM0001', 'name' => 'Ốc vít']);
        $fakeProduct->id = 1;

        $this->productRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'DM0001'))
            ->andReturn($fakeProduct); // ← dùng lại $fakeProduct, không tạo Product mới

        $this->reorderRuleService->shouldReceive('defaultWarehouseId')->andReturn(1);
        $this->reorderRuleService->shouldReceive('syncForProduct')->once();
        $this->putawayRuleService->shouldReceive('syncForProduct')->once();

        $result = $this->service->create([
            'name'        => 'Ốc vít',
            'category_id' => $category->id,
            'uom_id'      => $uom->id,
            'status'      => 1,
        ]);

        $this->assertSame('DM0001', $result->code);
    }

    public function test_create_normalizes_manual_code_to_uppercase(): void
    {
        $category = Category::factory()->create();
        $uom      = Uom::factory()->create();

        $this->codeGen->shouldNotReceive('generateCode');

        $fakeProduct = new Product(['code' => 'DD1234', 'name' => 'Dây điện']);
        $fakeProduct->id = 1;

        $this->productRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'DD1234'))
            ->andReturn($fakeProduct); // ← dùng lại $fakeProduct

        $this->reorderRuleService->shouldReceive('defaultWarehouseId')->andReturn(1);
        $this->reorderRuleService->shouldReceive('syncForProduct')->once();
        $this->putawayRuleService->shouldReceive('syncForProduct')->once();

        $result = $this->service->create([
            'code'        => ' dd1234 ',
            'name'        => 'Dây điện',
            'category_id' => $category->id,
            'uom_id'      => $uom->id,
            'status'      => 1,
        ]);

        $this->assertSame('DD1234', $result->code);
    }

    // ===== UPDATE =====

    public function test_update_strips_readonly_code_and_barcode_fields(): void
    {
        $product = Product::factory()->create();

        $this->productRepo->shouldReceive('update')
            ->once()
            ->with($product, Mockery::on(fn ($data) =>
                ! array_key_exists('code', $data) && ! array_key_exists('barcode', $data)
            ))
            ->andReturn(true);

        $this->reorderRuleService->shouldReceive('defaultWarehouseId')->andReturn(1);
        $this->reorderRuleService->shouldReceive('syncForProduct')->once();
        $this->putawayRuleService->shouldReceive('syncForProduct')->once();

        $this->service->update($product, [
            'code'    => 'HACKED', // cố tình gửi lên, phải bị loại bỏ
            'barcode' => '123456',
            'name'    => 'Tên mới',
        ]);
    }

    // ===== DELETE =====

    public function test_delete_throws_exception_when_product_has_stock(): void
    {
        $product = new Product(['name' => 'Ốc vít']);

        $this->productRepo->shouldReceive('hasStock')->once()->with($product)->andReturn(true);
        $this->productRepo->shouldNotReceive('delete');
        $this->reorderRuleService->shouldNotReceive('deleteForProduct');
        $this->putawayRuleService->shouldNotReceive('deleteForProduct');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xóa "Ốc vít" vì đang có tồn kho.');

        $this->service->delete($product);
    }

    public function test_delete_removes_related_rules_then_deletes_product(): void
    {
        $product = Product::factory()->create();

        $this->productRepo->shouldReceive('hasStock')->once()->with($product)->andReturn(false);
        $this->reorderRuleService->shouldReceive('deleteForProduct')->once()->with($product->id);
        $this->putawayRuleService->shouldReceive('deleteForProduct')->once()->with($product->id);
        $this->productRepo->shouldReceive('delete')->once()->with($product)->andReturn(true);

        $this->service->delete($product);
    }
}
