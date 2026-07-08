<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Category;
use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Master\PutawayRule;
use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class PutawayRuleFactory extends Factory
{
    protected $model = PutawayRule::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id'   => Product::factory(),
            'category_id'  => null,
            'location_id'  => Location::factory(),
            'note'         => $this->faker->optional()->sentence(),
            'status'       => ActiveStatus::Active->value,
        ];
    }

    /**
     * Rule áp dụng theo danh mục thay vì theo vật tư cụ thể.
     */
    public function forCategory(): static
    {
        return $this->state(fn () => [
            'product_id'  => null,
            'category_id' => Category::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}
