<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Employee;
use App\Models\Master\Product;
use App\Models\Master\ReorderRule;
use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReorderRuleFactory extends Factory
{
    protected $model = ReorderRule::class;

    public function definition(): array
    {
        return [
            'product_id'   => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'employee_id'  => Employee::factory(),
            'min_qty'      => 10,
            'max_qty'      => 100,
            'note'         => $this->faker->optional()->sentence(),
            'status'       => ActiveStatus::Active->value,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}
