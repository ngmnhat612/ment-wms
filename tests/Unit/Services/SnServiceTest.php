<?php

namespace Tests\Unit\Services;

use App\Models\Master\Sn;
use App\Repositories\Contracts\Master\SnRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Master\SnService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SnServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private SnRepositoryInterface $repo;
    private CodeGeneratorService $codeGen;
    private SnService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo    = Mockery::mock(SnRepositoryInterface::class);
        $this->codeGen = Mockery::mock(CodeGeneratorService::class);
        $this->service = new SnService($this->repo, $this->codeGen);
    }

    // ===== CREATE =====

    public function test_create_auto_generates_code_when_code_is_empty(): void
    {
        $this->codeGen->shouldReceive('generateCode')
            ->once()
            ->with('sns', 'code', 'DA', 4)
            ->andReturn('DA0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['code'] === 'DA0001'
                && $data['name'] === 'Dự án A'
                && $data['status'] === 1
            ))
            ->andReturn(new Sn(['code' => 'DA0001', 'name' => 'Dự án A']));

        $result = $this->service->create(['name' => 'Dự án A', 'status' => 1]);

        $this->assertSame('DA0001', $result->code);
    }

    public function test_create_normalizes_manual_code_to_uppercase_and_trims(): void
    {
        $this->codeGen->shouldNotReceive('generateCode');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'DA01'))
            ->andReturn(new Sn(['code' => 'DA01', 'name' => 'Dự án B']));

        $this->service->create(['code' => ' da01 ', 'name' => 'Dự án B', 'status' => 1]);
    }

    public function test_create_defaults_note_to_null_when_not_provided(): void
    {
        $this->codeGen->shouldReceive('generateCode')->once()->andReturn('DA0002');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => array_key_exists('note', $data) && $data['note'] === null))
            ->andReturn(new Sn(['code' => 'DA0002', 'name' => 'Dự án C']));

        $this->service->create(['name' => 'Dự án C', 'status' => 1]);
    }

    // ===== UPDATE =====

    public function test_update_trims_code_but_does_not_uppercase_it(): void
    {
        $sn = Mockery::mock(Sn::class)->makePartial();
        $sn->shouldReceive('fresh')->once()->andReturn($sn);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($sn, Mockery::on(fn ($data) => $data['code'] === 'da02'))
            ->andReturn(true);

        $result = $this->service->update($sn, ['code' => ' da02 ', 'name' => 'Dự án D', 'status' => 1]);

        $this->assertSame($sn, $result);
    }

    public function test_update_passes_through_name_note_and_status(): void
    {
        $sn = Mockery::mock(Sn::class)->makePartial();
        $sn->shouldReceive('fresh')->once()->andReturn($sn);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($sn, Mockery::on(fn ($data) =>
                $data['name'] === 'Dự án E'
                && $data['note'] === 'Ghi chú'
                && $data['status'] === 0
            ))
            ->andReturn(true);

        $this->service->update($sn, [
            'code'   => 'DA03',
            'name'   => 'Dự án E',
            'note'   => 'Ghi chú',
            'status' => 0,
        ]);
    }

    // ===== DELETE =====

    public function test_delete_calls_repository_delete_directly_without_dependency_check(): void
    {
        $sn = new Sn(['name' => 'Dự án A']);

        $this->repo->shouldNotReceive('search');
        $this->repo->shouldReceive('delete')->once()->with($sn)->andReturn(true);

        $this->service->delete($sn);

        $this->assertTrue(true); // không throw
    }
}
