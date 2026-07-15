<?php

namespace Tests\Unit\Services;

use App\Models\Master\Account;
use App\Models\Master\Employee;
use App\Repositories\Contracts\Master\EmployeeRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use App\Services\Master\AccountService;
use App\Services\Master\EmployeeService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class EmployeeServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private EmployeeRepositoryInterface $repo;
    private AccountService $accountService;
    private CodeGeneratorService $codeGen;
    private EmployeeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // EmployeeService::update()/delete() bọc logic trong DB::transaction().
        // Mock facade DB bằng alias mock của Mockery (không cần bootstrap
        // Laravel container thật) để transaction() chạy thẳng callback.
        $db = Mockery::mock('alias:Illuminate\Support\Facades\DB');
        $db->shouldReceive('transaction')
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->repo           = Mockery::mock(EmployeeRepositoryInterface::class);
        $this->accountService = Mockery::mock(AccountService::class);
        $this->codeGen        = Mockery::mock(CodeGeneratorService::class);
        $this->service         = new EmployeeService($this->repo, $this->accountService, $this->codeGen);
    }

    // ===== CREATE =====

    public function test_create_auto_generates_code_when_code_is_empty(): void
    {
        $this->codeGen->shouldReceive('generateCode')
            ->once()
            ->with('employees', 'code', 'NV', 4)
            ->andReturn('NV0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['code'] === 'NV0001'
                && $data['name'] === 'Nguyễn Văn A'
                && $data['unique_name'] === 'Nguyễn Văn A NV0001'
                && $data['status'] === 1
            ))
            ->andReturn(new Employee(['code' => 'NV0001', 'name' => 'Nguyễn Văn A']));

        $result = $this->service->create(['name' => 'Nguyễn Văn A', 'status' => 1, 'department_id' => null]);

        $this->assertSame('NV0001', $result->code);
    }

    public function test_create_normalizes_manual_code_to_uppercase_and_trims(): void
    {
        $this->codeGen->shouldNotReceive('generateCode');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['code'] === 'NV0099'))
            ->andReturn(new Employee(['code' => 'NV0099', 'name' => 'Nguyễn Văn B']));

        $this->service->create(['code' => ' nv0099 ', 'name' => 'Nguyễn Văn B', 'status' => 1, 'department_id' => null]);
    }

    public function test_create_sets_department_id_null_when_empty(): void
    {
        $this->codeGen->shouldReceive('generateCode')->once()->andReturn('NV0001');

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['department_id'] === null))
            ->andReturn(new Employee(['code' => 'NV0001', 'name' => 'Nguyễn Văn A']));

        $this->service->create(['name' => 'Nguyễn Văn A', 'status' => 1, 'department_id' => '']);
    }

    // ===== UPDATE =====

    public function test_update_returns_fresh_model_and_does_not_touch_account_when_staying_active(): void
    {
        $employee = Mockery::mock(Employee::class)->makePartial();
        $employee->shouldReceive('fresh')->once()->andReturn($employee);
        $employee->code = 'NV0001';

        $this->repo->shouldReceive('update')
            ->once()
            ->with($employee, Mockery::on(fn ($data) =>
                $data['name'] === 'Tên mới'
                && $data['unique_name'] === 'Tên mới NV0001'
            ))
            ->andReturn(true);

        $this->accountService->shouldNotReceive('deactivate');

        $result = $this->service->update($employee, ['name' => 'Tên mới', 'status' => 1, 'department_id' => null]);

        $this->assertSame($employee, $result);
    }

    public function test_update_deactivates_linked_account_when_becoming_inactive(): void
    {
        $account = Mockery::mock(Account::class)->makePartial();
        $account->is_protected = false;

        $employee = Mockery::mock(Employee::class)->makePartial();
        $employee->shouldReceive('fresh')->once()->andReturn($employee);
        $employee->code    = 'NV0001';
        $employee->account = $account;

        $this->repo->shouldReceive('update')->once()->andReturn(true);

        $this->accountService->shouldReceive('deactivate')->once()->with($account);

        $this->service->update($employee, ['name' => 'Tên A', 'status' => 0, 'department_id' => null]);
    }

    public function test_update_does_not_deactivate_protected_account_when_becoming_inactive(): void
    {
        $account = Mockery::mock(Account::class)->makePartial();
        $account->is_protected = true;

        $employee = Mockery::mock(Employee::class)->makePartial();
        $employee->shouldReceive('fresh')->once()->andReturn($employee);
        $employee->code    = 'NV0001';
        $employee->account = $account;

        $this->repo->shouldReceive('update')->once()->andReturn(true);

        $this->accountService->shouldNotReceive('deactivate');

        $this->service->update($employee, ['name' => 'Tên A', 'status' => 0, 'department_id' => null]);
    }

    public function test_update_does_nothing_to_account_when_employee_has_no_account(): void
    {
        $employee = Mockery::mock(Employee::class)->makePartial();
        $employee->shouldReceive('fresh')->once()->andReturn($employee);
        $employee->code    = 'NV0001';
        $employee->account = null;

        $this->repo->shouldReceive('update')->once()->andReturn(true);

        $this->accountService->shouldNotReceive('deactivate');

        $this->service->update($employee, ['name' => 'Tên A', 'status' => 0, 'department_id' => null]);
    }

    // ===== DELETE =====

    public function test_delete_removes_employee_without_account(): void
    {
        $employee = Mockery::mock(Employee::class)->makePartial();
        $employee->account = null;

        $this->repo->shouldReceive('delete')->once()->with($employee)->andReturn(true);

        $this->service->delete($employee);

        $this->assertTrue(true); // không throw
    }

    public function test_delete_cascades_account_deletion_when_account_is_not_protected(): void
    {
        $account = Mockery::mock(Account::class)->makePartial();
        $account->is_protected = false;

        $employee = Mockery::mock(Employee::class)->makePartial();
        $employee->account = $account;

        $this->accountService->shouldReceive('delete')->once()->with($account);
        $this->repo->shouldReceive('delete')->once()->with($employee)->andReturn(true);

        $this->service->delete($employee);

        $this->assertTrue(true); // không throw
    }

    public function test_delete_throws_exception_when_account_is_protected(): void
    {
        $account = Mockery::mock(Account::class)->makePartial();
        $account->is_protected = true;

        $employee = Mockery::mock(Employee::class)->makePartial();
        $employee->name    = 'Nguyễn Văn A';
        $employee->account = $account;

        $this->accountService->shouldNotReceive('delete');
        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xoá nhân viên "Nguyễn Văn A" vì tài khoản được bảo vệ.');

        $this->service->delete($employee);
    }
}
