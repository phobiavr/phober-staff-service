<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\SnackSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SnackSale>
 */
class SnackSaleFactory extends Factory
{
    protected $model = SnackSale::class;

    public function definition(): array
    {
        return [
            'snack' => $this->faker->words(2, true),
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 1, 20),
            'invoice_id' => Invoice::factory(),
        ];
    }
}
