<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Sn;
use Illuminate\Database\Eloquent\Factories\Factory;

class SnFactory extends Factory
{
    protected $model = Sn::class;

    public function definition(): array
    {
        return [
            'code'   => strtoupper($this->faker->unique()->bothify('DA####')),
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
