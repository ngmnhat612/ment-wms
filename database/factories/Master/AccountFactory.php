<?php
namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Account;
use App\Models\Master\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'employee_id'  => Employee::factory(),
            'username'     => $this->faker->unique()->userName(),
            'password'     => Hash::make('password'),
            'status'       => ActiveStatus::Active->value,
            'is_protected' => false,
        ];
    }
}
