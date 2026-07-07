<?php

namespace Tests\Unit\Services;

use App\Models\Master\Supplier;
use App\Repositories\Contracts\Master\SupplierRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Master\SupplierService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SupplierServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private SupplierRepositoryInterface $repo;
    private CodeGeneratorService $codeGen;
    private SupplierService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo    = Mockery::mock(SupplierRepositoryInterface::class);
        $this->codeGen = Mockery::mock(CodeGeneratorService::class);
        $this->service = new SupplierService($this->repo, $this->codeGen);
    }

    // ===== CREATE =====

    public function test_create_auto_generates_code_when_code_is_empty(): void
    {
        $this->codeGen->shouldReceive('generateCode')
            ->once()
            ->with('suppliers', 'code', 'NCC', 4)
            ->andReturn('NCC0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['code'] === 'NCC0001'
                && $data['name'] === 'Công ty A'
                && $data['status'] === 1
            ))
            ->andReturn(new Supplier(['code' => 'NCC0001', 'name' => 'Công ty A']));

        $result = $this->service->create(['name' => 'Công ty A', 'status' => 1]);

        $this->assertSame('NCC0001', $result->code);
    }

    public function test_create_normalizes_manual_code_to_uppercase_and_trims(): void
    {
        $this->codeGen->shouldNotReceive('generateCode');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'NCC01'))
            ->andReturn(new Supplier(['code' => 'NCC01', 'name' => 'Công ty B']));

        $this->service->create(['code' => ' ncc01 ', 'name' => 'Công ty B', 'status' => 1]);
    }

    public function test_create_defaults_optional_fields_to_null_when_not_provided(): void
    {
        $this->codeGen->shouldReceive('generateCode')->once()->andReturn('NCC0002');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['tax_code'] === null
                && $data['phone'] === null
                && $data['email'] === null
                && $data['address'] === null
                && $data['note'] === null
            ))
            ->andReturn(new Supplier(['code' => 'NCC0002', 'name' => 'Công ty C']));

        $this->service->create(['name' => 'Công ty C', 'status' => 1]);
    }

    // ===== UPDATE =====

    public function test_update_normalizes_manual_code_to_uppercase_and_trims(): void
    {
        $supplier = Mockery::mock(Supplier::class)->makePartial();
        $supplier->shouldReceive('fresh')->once()->andReturn($supplier);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($supplier, Mockery::on(fn ($data) => $data['code'] === 'NCC02'))
            ->andReturn(true);

        $result = $this->service->update($supplier, ['code' => ' ncc02 ', 'name' => 'Công ty D', 'status' => 1]);

        $this->assertSame($supplier, $result);
    }

    public function test_update_keeps_existing_code_when_code_is_empty(): void
    {
        $supplier = Mockery::mock(Supplier::class)->makePartial();
        $supplier->code = 'NCC0003';
        $supplier->shouldReceive('fresh')->once()->andReturn($supplier);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($supplier, Mockery::on(fn ($data) => $data['code'] === 'NCC0003'))
            ->andReturn(true);

        $this->service->update($supplier, ['name' => 'Công ty D', 'status' => 1]);
    }

    public function test_update_passes_through_optional_fields(): void
    {
        $supplier = Mockery::mock(Supplier::class)->makePartial();
        $supplier->code = 'NCC0004';
        $supplier->shouldReceive('fresh')->once()->andReturn($supplier);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($supplier, Mockery::on(fn ($data) =>
                $data['tax_code'] === '0312345678'
                && $data['phone'] === '0909000111'
                && $data['email'] === 'a@b.com'
                && $data['address'] === '123 Đường ABC'
                && $data['note'] === 'Ghi chú'
            ))
            ->andReturn(true);

        $this->service->update($supplier, [
            'name'     => 'Công ty E',
            'tax_code' => '0312345678',
            'phone'    => '0909000111',
            'email'    => 'a@b.com',
            'address'  => '123 Đường ABC',
            'note'     => 'Ghi chú',
            'status'   => 1,
        ]);
    }

    // ===== DELETE =====

    public function test_delete_removes_supplier_when_no_stock_receipts(): void
    {
        $supplier = new Supplier(['name' => 'Công ty A']);

        $this->repo->shouldReceive('hasStockReceipts')->once()->with($supplier)->andReturn(false);
        $this->repo->shouldReceive('delete')->once()->with($supplier)->andReturn(true);

        $this->service->delete($supplier);

        $this->assertTrue(true); // không throw
    }

    public function test_delete_throws_exception_when_supplier_has_stock_receipts(): void
    {
        $supplier = new Supplier(['name' => 'Công ty A']);

        $this->repo->shouldReceive('hasStockReceipts')->once()->with($supplier)->andReturn(true);
        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xóa "Công ty A" vì đang có phiếu nhập kho liên quan.');

        $this->service->delete($supplier);
    }
}
