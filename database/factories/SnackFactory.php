<?php

namespace Database\Factories;

use App\Models\Snack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Snack>
 */
class SnackFactory extends Factory
{
    protected $model = Snack::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'stock' => $this->faker->numberBetween(5, 50),
            'price' => $this->faker->randomFloat(2, 1, 20),
        ];
    }
}
