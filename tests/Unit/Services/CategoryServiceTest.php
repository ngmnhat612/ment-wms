<?php

namespace Tests\Unit\Services;

use App\Models\Master\Category;
use App\Repositories\Contracts\Master\CategoryRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Master\CategoryService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CategoryServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private CategoryRepositoryInterface $repo;
    private CodeGeneratorService $codeGen;
    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo    = Mockery::mock(CategoryRepositoryInterface::class);
        $this->codeGen = Mockery::mock(CodeGeneratorService::class);
        $this->service = new CategoryService($this->repo, $this->codeGen);
    }

    // ===== CREATE =====

    public function test_create_auto_generates_code_when_code_is_empty(): void
    {
        $this->codeGen->shouldReceive('generateCode')
            ->once()
            ->with('categories', 'code', 'DM', 4)
            ->andReturn('DM0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['code'] === 'DM0001'
                && $data['name'] === 'Nhóm A'
                && $data['status'] === 1
            ))
            ->andReturn(new Category(['code' => 'DM0001', 'name' => 'Nhóm A']));

        $result = $this->service->create(['name' => 'Nhóm A', 'status' => 1]);

        $this->assertSame('DM0001', $result->code);
    }

    public function test_create_normalizes_manual_code_to_uppercase_and_trims(): void
    {
        $this->codeGen->shouldNotReceive('generateCode');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'DM'))
            ->andReturn(new Category(['code' => 'DM', 'name' => 'Nhóm A']));

        $this->service->create(['code' => ' dm ', 'name' => 'Nhóm A', 'status' => 1]);
    }

    // ===== UPDATE =====

    public function test_update_normalizes_code_and_returns_fresh_model(): void
    {
        $category = Mockery::mock(Category::class)->makePartial();
        $category->shouldReceive('fresh')->once()->andReturn($category);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($category, Mockery::on(fn ($data) => $data['code'] === 'DM0002'))
            ->andReturn(true);

        $result = $this->service->update($category, ['code' => ' dm0002 ', 'name' => 'Nhóm B', 'status' => 1]);

        $this->assertSame($category, $result);
    }

    // ===== DELETE =====

    public function test_delete_removes_category_when_not_used_by_products(): void
    {
        $category = new Category(['name' => 'Nhóm A']);

        $this->repo->shouldReceive('hasProducts')->once()->with($category)->andReturn(false);
        $this->repo->shouldReceive('delete')->once()->with($category)->andReturn(true);

        $this->service->delete($category);

        $this->assertTrue(true); // không throw
    }

    public function test_delete_throws_exception_when_category_used_by_products(): void
    {
        $category = new Category(['name' => 'Nhóm A']);

        $this->repo->shouldReceive('hasProducts')->once()->with($category)->andReturn(true);
        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xóa nếu đã gán danh mục vật tư.');

        $this->service->delete($category);
    }
}
