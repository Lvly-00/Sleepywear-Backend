<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Collection',
            'release_date' => $this->faker->date(),
            'qty' => $this->faker->numberBetween(50, 500),
            'status' => $this->faker->randomElement(['Active', 'Sold Out']),
            'stock_qty' => $this->faker->numberBetween(10, 400),
            'capital' => $this->faker->randomFloat(2, 500, 50000),
        ];
    }
}
