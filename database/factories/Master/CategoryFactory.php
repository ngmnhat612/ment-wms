<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'code'      => strtoupper($this->faker->unique()->bothify('DM####')),
            'name'      => $this->faker->unique()->words(2, true),
            'parent_id' => null,
            'note'      => $this->faker->optional()->sentence(),
            'status'    => ActiveStatus::Active->value,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }

    public function withParent(Category $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
