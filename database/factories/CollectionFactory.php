<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true).' Collection',
            'release_date' => $this->faker->date(),
            'qty' => $this->faker->numberBetween(50, 500),
            'status' => $this->faker->randomElement(['Active', 'Sold Out']),
            'stock_qty' => $this->faker->numberBetween(10, 400),
            'capital' => $this->faker->numberBetween(500, 50000), // integer capital
            'total_sales' => 0, // default to 0 for seeding
        ];
    }
}
