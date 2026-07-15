<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'code'   => strtoupper($this->faker->unique()->bothify('KHO####')),
            'name'   => $this->faker->unique()->words(2, true),
            'status' => ActiveStatus::Active->value,
        ];
    }
}
