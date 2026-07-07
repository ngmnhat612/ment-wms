<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

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

    public function withParent(Location $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
