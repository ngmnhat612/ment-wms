<?php

namespace Tests\Unit\Services;

use App\Enums\LocationType;
use App\Models\Master\Location;
use App\Repositories\Contracts\Master\LocationRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Master\LocationService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LocationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private LocationRepositoryInterface $repo;
    private CodeGeneratorService $codeGen;
    private LocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo    = Mockery::mock(LocationRepositoryInterface::class);
        $this->codeGen = Mockery::mock(CodeGeneratorService::class);
        $this->service  = new LocationService($this->repo, $this->codeGen);
    }

    // =========================================================
    // ===== CREATE =====
    // =========================================================

    public function test_create_auto_generates_code_when_code_is_empty(): void
    {
        $this->codeGen->shouldReceive('generateCode')
            ->once()
            ->with('locations', 'code', 'VT', 4)
            ->andReturn('VT0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['code'] === 'VT0001'
                && $data['name'] === 'Kệ A1'
                && $data['type'] === LocationType::Internal->value
                && $data['status'] === 1
            ))
            ->andReturn(new Location(['code' => 'VT0001', 'name' => 'Kệ A1']));

        $result = $this->service->create(['name' => 'Kệ A1', 'status' => 1]);

        $this->assertSame('VT0001', $result->code);
    }

    public function test_create_normalizes_manual_code_to_uppercase_and_trims(): void
    {
        $this->codeGen->shouldNotReceive('generateCode');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'KEA1'))
            ->andReturn(new Location(['code' => 'KEA1', 'name' => 'Kệ A1']));

        $this->service->create(['code' => ' kea1 ', 'name' => 'Kệ A1', 'status' => 1]);
    }

    public function test_create_keeps_parent_id_when_type_is_internal(): void
    {
        $this->codeGen->shouldReceive('generateCode')->once()->andReturn('VT0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['parent_id'] === 5))
            ->andReturn(new Location());

        $this->service->create([
            'name'      => 'Kệ A1',
            'status'    => 1,
            'type'      => LocationType::Internal->value,
            'parent_id' => 5,
        ]);
    }

    public function test_create_forces_parent_id_to_null_when_type_is_virtual(): void
    {
        $this->codeGen->shouldReceive('generateCode')->once()->andReturn('VT0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['parent_id'] === null))
            ->andReturn(new Location());

        $this->service->create([
            'name'   => 'Vị trí ảo',
            'status' => 1,
            'type'   => LocationType::Virtual->value,
        ]);
    }

    public function test_create_throws_exception_when_virtual_type_has_parent(): void
    {
        $this->repo->shouldNotReceive('create');
        $this->codeGen->shouldNotReceive('generateCode');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Vị trí ảo (Virtual) không thể có vị trí cha.');

        $this->service->create([
            'name'      => 'Vị trí ảo',
            'status'    => 1,
            'type'      => LocationType::Virtual->value,
            'parent_id' => 5,
        ]);
    }

    // =========================================================
    // ===== UPDATE =====
    // =========================================================

    public function test_update_normalizes_code_and_returns_fresh_model(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->id = 1;
        $location->shouldReceive('fresh')->once()->andReturn($location);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($location, Mockery::on(fn ($data) => $data['code'] === 'KEA1'))
            ->andReturn(true);

        $result = $this->service->update($location, [
            'code'   => ' kea1 ',
            'name'   => 'Kệ A1',
            'status' => 1,
            'type'   => LocationType::Internal->value,
        ]);

        $this->assertSame($location, $result);
    }

    public function test_update_throws_exception_when_virtual_type_has_parent(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->id = 1;

        $this->repo->shouldNotReceive('update');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Vị trí ảo (Virtual) không thể có vị trí cha.');

        $this->service->update($location, [
            'code'      => 'VT0002',
            'name'      => 'Vị trí ảo',
            'status'    => 1,
            'type'      => LocationType::Virtual->value,
            'parent_id' => 5,
        ]);
    }

    public function test_update_throws_exception_when_selecting_itself_as_parent(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->id = 10;

        $this->repo->shouldNotReceive('update');
        $this->repo->shouldNotReceive('getDescendantIds');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể chọn chính vị trí này làm vị trí cha.');

        $this->service->update($location, [
            'code'      => 'KEA1',
            'name'      => 'Kệ A1',
            'status'    => 1,
            'type'      => LocationType::Internal->value,
            'parent_id' => 10,
        ]);
    }

    public function test_update_throws_exception_when_selecting_a_descendant_as_parent(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->id = 10;

        $this->repo->shouldReceive('getDescendantIds')
            ->once()
            ->with($location)
            ->andReturn([20, 21]);

        $this->repo->shouldNotReceive('update');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể chọn vị trí con làm vị trí cha.');

        $this->service->update($location, [
            'code'      => 'KEA1',
            'name'      => 'Kệ A1',
            'status'    => 1,
            'type'      => LocationType::Internal->value,
            'parent_id' => 21,
        ]);
    }

    public function test_update_forces_parent_id_to_null_when_type_is_virtual_without_parent(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->id = 1;
        $location->shouldReceive('fresh')->once()->andReturn($location);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($location, Mockery::on(fn ($data) => $data['parent_id'] === null))
            ->andReturn(true);

        $this->service->update($location, [
            'code'   => 'VT0002',
            'name'   => 'Vị trí ảo',
            'status' => 1,
            'type'   => LocationType::Virtual->value,
        ]);
    }

    // =========================================================
    // ===== DELETE =====
    // =========================================================

    public function test_delete_removes_location_when_no_children_stock_or_root(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->name = 'Kệ A1';

        $this->repo->shouldReceive('hasChildren')->once()->with($location)->andReturn(false);
        $this->repo->shouldReceive('hasStock')->once()->with($location)->andReturn(false);
        $this->repo->shouldReceive('isRootLocation')->once()->with($location)->andReturn(false);
        $this->repo->shouldReceive('delete')->once()->with($location)->andReturn(true);

        $this->service->delete($location);

        $this->assertTrue(true); // không throw
    }

    public function test_delete_throws_exception_when_location_has_children(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->name = 'Kệ A1';

        $this->repo->shouldReceive('hasChildren')->once()->with($location)->andReturn(true);
        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xóa "Kệ A1" vì có vị trí con.');

        $this->service->delete($location);
    }

    public function test_delete_throws_exception_when_location_has_stock(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->name = 'Kệ A1';

        $this->repo->shouldReceive('hasChildren')->once()->with($location)->andReturn(false);
        $this->repo->shouldReceive('hasStock')->once()->with($location)->andReturn(true);
        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xóa "Kệ A1" vì đang có tồn kho.');

        $this->service->delete($location);
    }

    public function test_delete_throws_exception_when_location_is_root_of_warehouse(): void
    {
        $location = Mockery::mock(Location::class)->makePartial();
        $location->name = 'Kho tổng';

        $this->repo->shouldReceive('hasChildren')->once()->with($location)->andReturn(false);
        $this->repo->shouldReceive('hasStock')->once()->with($location)->andReturn(false);
        $this->repo->shouldReceive('isRootLocation')->once()->with($location)->andReturn(true);
        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xóa vị trí gốc của kho "Kho tổng".');

        $this->service->delete($location);
    }

    // =========================================================
    // ===== TREE =====
    // =========================================================

    public function test_get_tree_roots_returns_only_locations_without_parent(): void
    {
        $root1 = new Location(['name' => 'Vị trí ảo']);
        $root1->id = 1;
        $root1->parent_id = null;

        $child = new Location(['name' => 'Kệ A1']);
        $child->id = 2;
        $child->parent_id = 1;

        $this->repo->shouldReceive('allForTree')
            ->once()
            ->andReturn(new Collection([$root1, $child]));

        $roots = $this->service->getTreeRoots();

        $this->assertCount(1, $roots);
        $this->assertSame($root1, $roots->first());
    }
}
