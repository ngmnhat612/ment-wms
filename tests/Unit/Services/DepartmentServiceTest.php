<?php

namespace Tests\Unit\Services;

use App\Models\Master\Department;
use App\Repositories\Contracts\Master\DepartmentRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Master\DepartmentService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DepartmentServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private DepartmentRepositoryInterface $repo;
    private CodeGeneratorService $codeGen;
    private DepartmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo    = Mockery::mock(DepartmentRepositoryInterface::class);
        $this->codeGen = Mockery::mock(CodeGeneratorService::class);
        $this->service = new DepartmentService($this->repo, $this->codeGen);
    }

    // ===== CREATE =====

    public function test_create_auto_generates_code_when_code_is_empty(): void
    {
        $this->codeGen->shouldReceive('generateCode')
            ->once()
            ->with('departments', 'code', 'BP', 4)
            ->andReturn('BP0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['code'] === 'BP0001'
                && $data['name'] === 'Kho'
                && $data['status'] === 1
            ))
            ->andReturn(new Department(['code' => 'BP0001', 'name' => 'Kho']));

        $result = $this->service->create(['name' => 'Kho', 'status' => 1]);

        $this->assertSame('BP0001', $result->code);
    }

    public function test_create_normalizes_manual_code_to_uppercase_and_trims(): void
    {
        $this->codeGen->shouldNotReceive('generateCode');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'KHO'))
            ->andReturn(new Department(['code' => 'KHO', 'name' => 'Kho']));

        $this->service->create(['code' => ' kho ', 'name' => 'Kho', 'status' => 1]);
    }

    // ===== UPDATE =====

    public function test_update_normalizes_code_and_returns_fresh_model(): void
    {
        $department = Mockery::mock(Department::class)->makePartial();
        $department->shouldReceive('fresh')->once()->andReturn($department);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($department, Mockery::on(fn ($data) => $data['code'] === 'KT'))
            ->andReturn(true);

        $result = $this->service->update($department, ['code' => ' kt ', 'name' => 'Kế toán', 'status' => 1]);

        $this->assertSame($department, $result);
    }

    // ===== DELETE =====

    /**
     * NOTE: khác với Uom/Supplier (kiểm tra qua repository: hasProducts()/hasStockReceipts()),
     * DepartmentService::delete() gọi trực tiếp $department->employees()->exists()
     * trên model, không đi qua DepartmentRepositoryInterface.
     */
    public function test_delete_removes_department_when_no_employees(): void
    {
        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('exists')->once()->andReturn(false);

        $department = Mockery::mock(Department::class)->makePartial();
        $department->shouldReceive('employees')->once()->andReturn($relation);

        $this->repo->shouldReceive('delete')->once()->with($department)->andReturn(true);

        $this->service->delete($department);

        $this->assertTrue(true); // không throw
    }

    public function test_delete_throws_exception_when_department_has_employees(): void
    {
        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('exists')->once()->andReturn(true);

        $department = Mockery::mock(Department::class)->makePartial();
        $department->shouldReceive('employees')->once()->andReturn($relation);

        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xóa bộ phận đã gán cho nhân viên.');

        $this->service->delete($department);
    }
}
