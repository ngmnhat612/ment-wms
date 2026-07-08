<?php

namespace Database\Factories\Master;

use App\Enums\ActiveStatus;
use App\Enums\StockRotation;
use App\Enums\TrackingType;
use App\Models\Master\Category;
use App\Models\Master\Product;
use App\Models\Master\Uom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'code'                => strtoupper($this->faker->unique()->bothify('SP####')),
            'name'                => $this->faker->unique()->words(3, true),
            'category_id'         => Category::factory(),
            'uom_id'              => Uom::factory(),
            'parent_id'           => null,
            'specification'       => $this->faker->optional()->sentence(),
            'alert_before_expiry' => null,
            'stock_rotation'      => StockRotation::FIFO->value,
            'image_path'          => null,
            'status'              => ActiveStatus::Active->value,
            'tracking_type'       => TrackingType::Lot->value,
        ];
    }

    public function variantOf(Product $parent): static
    {
        return $this->state(fn () => [
            'parent_id'           => $parent->id,
            'category_id'         => $parent->category_id,
            'uom_id'              => $parent->uom_id,
            'tracking_type'       => $parent->tracking_type->value,
            'stock_rotation'      => $parent->stock_rotation->value,
            'alert_before_expiry' => $parent->alert_before_expiry,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => ActiveStatus::Inactive->value]);
    }
}
