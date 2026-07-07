<?php

namespace Tests\Unit\Services;

use App\Models\Master\Uom;
use App\Repositories\Contracts\Master\UomRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Master\UomService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UomServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private UomRepositoryInterface $repo;
    private CodeGeneratorService $codeGen;
    private UomService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo    = Mockery::mock(UomRepositoryInterface::class);
        $this->codeGen = Mockery::mock(CodeGeneratorService::class);
        $this->service = new UomService($this->repo, $this->codeGen);
    }

    public function test_create_auto_generates_code_when_code_is_empty(): void
    {
        $this->codeGen->shouldReceive('generateCode')
            ->once()
            ->with('uoms', 'code', 'DVT', 4)
            ->andReturn('DVT0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['code'] === 'DVT0001'
                && $data['name'] === 'Cái'
                && $data['status'] === 1
            ))
            ->andReturn(new Uom(['code' => 'DVT0001', 'name' => 'Cái']));

        $result = $this->service->create(['name' => 'Cái', 'status' => 1]);

        $this->assertSame('DVT0001', $result->code);
    }

    public function test_create_normalizes_manual_code_to_uppercase_and_trims(): void
    {
        $this->codeGen->shouldNotReceive('generateCode');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'KG'))
            ->andReturn(new Uom(['code' => 'KG', 'name' => 'Kg']));

        $this->service->create(['code' => ' kg ', 'name' => 'Kg', 'status' => 1]);
    }

    public function test_update_normalizes_code_and_returns_fresh_model(): void
    {
        $uom = Mockery::mock(Uom::class)->makePartial();
        $uom->shouldReceive('fresh')->once()->andReturn($uom);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($uom, Mockery::on(fn ($data) => $data['code'] === 'HOP'))
            ->andReturn(true);

        $result = $this->service->update($uom, ['code' => 'hop', 'name' => 'Hộp', 'status' => 1]);

        $this->assertSame($uom, $result);
    }

    public function test_delete_removes_uom_when_not_used_by_products(): void
    {
        $uom = new Uom(['name' => 'Cái']);

        $this->repo->shouldReceive('hasProducts')->once()->with($uom)->andReturn(false);
        $this->repo->shouldReceive('delete')->once()->with($uom)->andReturn(true);

        $this->service->delete($uom);

        $this->assertTrue(true); // không throw
    }

    public function test_delete_throws_exception_when_uom_used_by_products(): void
    {
        $uom = new Uom(['name' => 'Cái']);

        $this->repo->shouldReceive('hasProducts')->once()->with($uom)->andReturn(true);
        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xóa "Cái" vì đã được gán cho vật tư.');

        $this->service->delete($uom);
    }
}