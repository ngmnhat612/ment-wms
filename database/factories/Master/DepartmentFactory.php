<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'code'   => strtoupper($this->faker->unique()->bothify('BP####')),
            'name'   => $this->faker->unique()->words(2, true),
            'note'   => $this->faker->optional()->sentence(),
            'status' => ActiveStatus::Active->value,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}
