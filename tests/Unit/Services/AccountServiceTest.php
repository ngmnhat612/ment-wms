<?php

namespace Tests\Unit\Services;

use App\Models\Master\Account;
use App\Models\Master\Employee;
use App\Repositories\Contracts\Master\AccountRepositoryInterface;
use App\Services\Master\AccountService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase, MockeryPHPUnitIntegration;

    private AccountRepositoryInterface $repo;
    private AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->repo    = Mockery::mock(AccountRepositoryInterface::class);
        $this->service = new AccountService($this->repo);
    }

    // ===== CREATE =====

    public function test_create_throws_exception_when_employee_already_has_account(): void
    {
        $employee = Employee::factory()->create(['name' => 'Nguyễn Văn A']);
        Account::factory()->create(['employee_id' => $employee->id]);

        $this->repo->shouldNotReceive('create');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nhân viên "Nguyễn Văn A" đã có tài khoản.');

        $this->service->create($employee, [
            'username' => 'nva2',
            'password' => 'password123',
            'role'     => 'Nhân viên',
        ]);
    }

    public function test_create_hashes_password_and_assigns_role(): void
    {
        $employee = Employee::factory()->create();

        $this->repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) =>
                $data['employee_id'] === $employee->id
                && $data['username'] === 'nva'
                && $data['status'] === 1
                && password_verify('password123', $data['password'])
            ))
            ->andReturnUsing(fn ($data) => Account::create($data));

        $account = $this->service->create($employee, [
            'username'       => 'nva',
            'password'       => 'password123',
            'role'           => 'Nhân viên',
            'account_status' => 1,
        ]);

        $this->assertTrue($account->hasRole('Nhân viên'));
    }

    // ===== UPDATE =====

    public function test_update_hashes_new_password_when_provided(): void
    {
        $account = Account::factory()->create();

        $this->repo->shouldReceive('update')
            ->once()
            ->with($account, Mockery::on(fn ($data) =>
                $data['status'] === 0
                && password_verify('newpass123', $data['password'])
            ))
            ->andReturn(true);

        $this->service->update($account, [
            'account_status' => 0,
            'new_password'   => 'newpass123',
            'role'           => 'Quản lý',
        ]);
    }

    public function test_update_skips_password_when_not_provided(): void
    {
        $account = Account::factory()->create();

        $this->repo->shouldReceive('update')
            ->once()
            ->with($account, Mockery::on(fn ($data) =>
                ! array_key_exists('password', $data) && $data['status'] === 1
            ))
            ->andReturn(true);

        $this->service->update($account, [
            'account_status' => 1,
            'new_password'   => '',
            'role'           => 'Nhân viên',
        ]);
    }

    // ===== DELETE =====

    public function test_delete_throws_exception_when_account_is_protected(): void
    {
        $account = Account::factory()->create(['is_protected' => true]);

        $this->repo->shouldNotReceive('delete');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Không thể xoá tài khoản được bảo vệ.');

        $this->service->delete($account);
    }

    public function test_delete_removes_account_when_not_protected(): void
    {
        $account = Account::factory()->create(['is_protected' => false]);

        $this->repo->shouldReceive('delete')->once()->with($account)->andReturn(true);

        $this->service->delete($account);
    }

    // ===== DEACTIVATE =====

    public function test_deactivate_silently_skips_protected_account(): void
    {
        $account = Account::factory()->create(['is_protected' => true]);

        $this->repo->shouldNotReceive('update');

        $this->service->deactivate($account);
    }

    public function test_deactivate_sets_status_inactive_for_normal_account(): void
    {
        $account = Account::factory()->create(['is_protected' => false]);

        $this->repo->shouldReceive('update')
            ->once()
            ->with($account, ['status' => 0])
            ->andReturn(true);

        $this->service->deactivate($account);
    }
}
