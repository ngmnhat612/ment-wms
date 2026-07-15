<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->name();
        $code = strtoupper($this->faker->unique()->bothify('NV####'));

        return [
            'code'         => $code,
            'name'         => $name,
            'unique_name'  => "{$name} ({$code})",
            'phone_number' => $this->faker->optional()->phoneNumber(),
            'department_id' => null,
            'status'       => ActiveStatus::Active->value,
            'note'         => null,
        ];
    }
}
