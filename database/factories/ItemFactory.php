<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('ITM###')),
            'name' => $this->faker->word() . ' Shirt',
            'image' => $this->faker->imageUrl(200, 200, 'fashion', true, 'clothes'),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'notes' => $this->faker->sentence(),
            'collection_stock_qty' => $this->faker->numberBetween(1, 100),
            'collection_id' => Collection::factory(), // will create a new collection if not specified
            'status' => $this->faker->randomElement(['available', 'taken']),
        ];
    }
}
